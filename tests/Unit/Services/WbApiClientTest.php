<?php

namespace Tests\Unit\Services;

use App\Models\AccountToken;
use App\Models\ApiService;
use App\Models\TokenType;
use App\Services\WbApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WbApiClientTest extends TestCase
{
    private function makeToken(string $slug = 'api_key', array $credentials = ['key' => 'test-key']): AccountToken
    {
        $token = new AccountToken([
            'account_id' => 1,
            'api_service_id' => 1,
            'token_type_id' => 1,
            'is_active' => true,
        ]);

        $service = new ApiService(['slug' => 'wb_test', 'base_url' => 'http://test-api.local']);
        $type = new TokenType(['slug' => $slug, 'name' => $slug]);
        $token->setRelation('apiService', $service);
        $token->setRelation('tokenType', $type);
        $token->setCredentialsArray($credentials);

        return $token;
    }

    public function test_api_key_is_sent_as_query_parameter(): void
    {
        Http::fake([
            'test-api.local/api/orders*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]], 200),
        ]);

        $client = app(WbApiClient::class)->forToken($this->makeToken());
        $response = $client->fetch('orders', '2024-01-01', '2024-01-31', 1);

        $this->assertTrue($response->successful());
        Http::assertSent(fn ($request) => $request->data()['key'] === 'test-key');
    }

    public function test_retries_on_http_429_and_succeeds(): void
    {
        Http::fake([
            'test-api.local/api/orders*' => Http::sequence()
                ->push('Too many requests', 429, ['Retry-After' => '1'])
                ->push(['data' => [['srid' => '1']], 'meta' => ['last_page' => 1]], 200),
        ]);

        $client = app(WbApiClient::class)->forToken($this->makeToken());
        $response = $client->fetch('orders', '2024-01-01', '2024-01-31', 1);

        $this->assertTrue($response->successful());
        Http::assertSentCount(2);
    }

    public function test_detects_too_many_requests_in_response_body(): void
    {
        Http::fake([
            'test-api.local/api/orders*' => Http::sequence()
                ->push(['error' => 'Too many requests'], 503, ['Retry-After' => '1'])
                ->push(['data' => [], 'meta' => ['last_page' => 1]], 200),
        ]);

        config(['wb_api.max_retries' => 2, 'wb_api.request_delay_ms' => 0]);

        $client = app(WbApiClient::class)->forToken($this->makeToken());
        $response = $client->fetch('orders', '2024-01-01', '2024-01-31', 1);

        $this->assertTrue($response->successful());
        Http::assertSentCount(2);
    }

    public function test_invalid_token_returns_error_without_exception(): void
    {
        Http::fake([
            'test-api.local/api/orders*' => Http::response(['error' => 'Forbidden'], 403),
        ]);

        $client = app(WbApiClient::class)->forToken($this->makeToken());
        $response = $client->fetch('orders', '2024-01-01', '2024-01-31', 1);

        $this->assertFalse($response->successful());
        $this->assertSame(403, $response->status());
    }

    public function test_bearer_token_is_sent_as_authorization_header(): void
    {
        Http::fake([
            'test-api.local/api/orders*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]], 200),
        ]);

        $client = app(WbApiClient::class)->forToken($this->makeToken('bearer', ['token' => 'bearer-xyz']));
        $client->fetch('orders', '2024-01-01', '2024-01-31', 1);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer bearer-xyz'));
    }

    public function test_exhausts_retries_and_returns_last_error(): void
    {
        Http::fake([
            'test-api.local/api/orders*' => Http::response('Too many requests', 429, ['Retry-After' => '1']),
        ]);

        config(['wb_api.max_retries' => 2, 'wb_api.request_delay_ms' => 0]);

        $client = app(WbApiClient::class)->forToken($this->makeToken());
        $response = $client->fetch('orders', '2024-01-01', '2024-01-31', 1);

        $this->assertFalse($response->successful());
        $this->assertSame(429, $response->status());
        Http::assertSentCount(2);
    }
}
