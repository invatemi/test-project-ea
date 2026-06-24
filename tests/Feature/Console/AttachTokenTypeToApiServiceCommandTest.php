<?php

namespace Tests\Feature\Console;

use App\Models\ApiService;
use App\Models\TokenType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttachTokenTypeToApiServiceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_attaches_token_type_to_service(): void
    {
        ApiService::query()->create(['slug' => 'svc', 'name' => 'S', 'base_url' => 'http://x']);
        TokenType::query()->firstOrCreate(['slug' => 'bearer'], ['name' => 'Bearer']);

        $this->artisan('app:api-service:attach-token-type', [
            'service_slug' => 'svc',
            'type_slug' => 'bearer',
        ])->assertSuccessful();

        $service = ApiService::findBySlug('svc');
        $this->assertTrue($service->tokenTypes()->where('slug', 'bearer')->exists());
    }

    public function test_fails_when_service_or_type_missing(): void
    {
        $this->artisan('app:api-service:attach-token-type', [
            'service_slug' => 'missing',
            'type_slug' => 'bearer',
        ])->assertFailed();
    }
}
