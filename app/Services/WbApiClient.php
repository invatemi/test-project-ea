<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WbApiClient
{
    private static ?float $lastRequestAt = null;

    public function fetch(string $endpoint, string $dateFrom, ?string $dateTo = null, int $page = 1): Response
    {
        $query = [
            'dateFrom' => $dateFrom,
            'page' => $page,
            'limit' => config('wb_api.limit'),
            'key' => config('wb_api.key'),
        ];

        if ($dateTo !== null) {
            $query['dateTo'] = $dateTo;
        }

        $url = rtrim(config('wb_api.host'), '/').'/api/'.$endpoint;
        $maxAttempts = max(1, (int) config('wb_api.max_retries'));

        $this->throttle();

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = Http::timeout(120)->get($url, $query);

            if ($response->successful()) {
                self::$lastRequestAt = microtime(true);

                return $response;
            }

            if ($response->status() === 429 && $attempt < $maxAttempts) {
                sleep(max(1, (int) ($response->header('Retry-After') ?: 60)));

                continue;
            }

            return $response;
        }

        return $response;
    }

    private function throttle(): void
    {
        $delayMs = (int) config('wb_api.request_delay_ms');

        if ($delayMs <= 0 || self::$lastRequestAt === null) {
            return;
        }

        $elapsedMs = (microtime(true) - self::$lastRequestAt) * 1000;

        if ($elapsedMs < $delayMs) {
            usleep((int) (($delayMs - $elapsedMs) * 1000));
        }
    }
}
