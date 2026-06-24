<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateApiServiceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_api_service(): void
    {
        $this->artisan('app:api-service:create', [
            'slug' => 'custom_api',
            'base_url' => 'https://api.example.com',
            'name' => 'Custom API',
        ])->assertSuccessful();

        $this->assertDatabaseHas('api_services', [
            'slug' => 'custom_api',
            'base_url' => 'https://api.example.com',
            'name' => 'Custom API',
        ]);
    }
}
