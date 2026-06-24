<?php

namespace App\Services;

use App\Models\AccountToken;
use Closure;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WbApiClient implements ApiClientInterface
{
    /** @var array<int, float> */
    private static array $lastRequestAt = [];

    private ?AccountToken $token = null;

    private bool $verbose = false;

    /** @var null|Closure(string): void */
    private ?Closure $debugLogger = null;

    public function forToken(AccountToken $token): self
    {
        $clone = clone $this;
        $clone->token = $token->loadMissing(['apiService', 'tokenType']);

        return $clone;
    }

    public function setVerbose(bool $verbose = true): self
    {
        $this->verbose = $verbose;

        return $this;
    }

    /** @param  Closure(string): void  $logger */
    public function setDebugLogger(Closure $logger): self
    {
        $this->debugLogger = $logger;

        return $this;
    }

    public function fetch(string $endpoint, string $dateFrom, ?string $dateTo = null, int $page = 1): Response
    {
        $query = [
            'dateFrom' => $dateFrom,
            'page' => $page,
            'limit' => config('wb_api.limit'),
        ];

        if ($dateTo !== null) {
            $query['dateTo'] = $dateTo;
        }

        $this->applyAuth($query);

        $baseUrl = rtrim($this->token?->apiService?->base_url ?? config('wb_api.host'), '/');
        $url = $baseUrl.'/api/'.$endpoint;
        $maxAttempts = max(1, (int) config('wb_api.max_retries'));
        $throttleKey = $this->token?->account_id ?? 0;

        $this->throttle($throttleKey);

        $response = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $this->debug("→ GET {$endpoint} page={$page} attempt={$attempt}/{$maxAttempts} dateFrom={$dateFrom}".($dateTo ? " dateTo={$dateTo}" : ''));

            $request = Http::timeout(120);
            $request = $this->applyRequestAuth($request);

            $response = $request->get($url, $query);

            if ($response->successful()) {
                self::$lastRequestAt[$throttleKey] = microtime(true);
                $this->debug("← HTTP {$response->status()} page={$page} endpoint={$endpoint}");

                return $response;
            }

            if ($this->isRateLimited($response) && $attempt < $maxAttempts) {
                $pause = $this->resolveRetryPause($response, $attempt);
                $this->debug("⚠ Too many requests (HTTP {$response->status()}), пауза {$pause}с, повтор...", true);
                Log::warning('WB API rate limit', [
                    'endpoint' => $endpoint,
                    'page' => $page,
                    'attempt' => $attempt,
                    'status' => $response->status(),
                    'pause_seconds' => $pause,
                ]);
                sleep($pause);

                continue;
            }

            $this->debug("← HTTP {$response->status()} FAILED page={$page} endpoint={$endpoint}", true);

            return $response;
        }

        return $response;
    }

    /** @param  array<string, mixed>  $query */
    private function applyAuth(array &$query): void
    {
        if ($this->token === null) {
            if (config('wb_api.key')) {
                $query['key'] = config('wb_api.key');
            }

            return;
        }

        $credentials = $this->token->getCredentialsArray();
        $slug = $this->token->tokenType?->slug ?? 'api_key';

        match ($slug) {
            'api_key' => $query['key'] = $credentials['key'] ?? $credentials['token'] ?? '',
            'bearer', 'login_password' => null,
            default => $query['key'] = $credentials['key'] ?? '',
        };
    }

    private function applyRequestAuth(\Illuminate\Http\Client\PendingRequest $request): \Illuminate\Http\Client\PendingRequest
    {
        if ($this->token === null) {
            return $request;
        }

        $credentials = $this->token->getCredentialsArray();
        $slug = $this->token->tokenType?->slug ?? 'api_key';

        return match ($slug) {
            'bearer' => $request->withToken($credentials['token'] ?? ''),
            'login_password' => $request->withBasicAuth(
                $credentials['login'] ?? '',
                $credentials['password'] ?? '',
            ),
            default => $request,
        };
    }

    private function isRateLimited(Response $response): bool
    {
        if ($response->status() === 429) {
            return true;
        }

        $body = strtolower($response->body());

        return str_contains($body, 'too many requests') || str_contains($body, 'rate limit');
    }

    private function resolveRetryPause(Response $response, int $attempt): int
    {
        $retryAfter = (int) ($response->header('Retry-After') ?: 0);

        if ($retryAfter > 0) {
            return $retryAfter;
        }

        return min(300, max(1, (int) (60 * (2 ** ($attempt - 1)))));
    }

    private function throttle(int $key): void
    {
        $delayMs = (int) config('wb_api.request_delay_ms');

        if ($delayMs <= 0 || ! isset(self::$lastRequestAt[$key])) {
            return;
        }

        $elapsedMs = (microtime(true) - self::$lastRequestAt[$key]) * 1000;

        if ($elapsedMs < $delayMs) {
            usleep((int) (($delayMs - $elapsedMs) * 1000));
        }
    }

    private function debug(string $message, bool $warn = false): void
    {
        if ($this->debugLogger !== null) {
            ($this->debugLogger)($message);

            return;
        }

        if (! $this->verbose) {
            return;
        }

        $line = '[WB API] '.$message;

        if ($warn) {
            Log::warning($line);
        } else {
            Log::debug($line);
        }

        if (defined('STDERR')) {
            fwrite(STDERR, $line.PHP_EOL);
        }
    }
}
