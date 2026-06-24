<?php

namespace Tests\Feature\Console;

use App\Models\TokenType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTokenTypeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_token_type(): void
    {
        $this->artisan('app:token-type:create', [
            'slug' => 'oauth2',
            'name' => 'OAuth 2.0',
        ])->assertSuccessful();

        $this->assertDatabaseHas('token_types', [
            'slug' => 'oauth2',
            'name' => 'OAuth 2.0',
        ]);
    }

    public function test_uses_slug_as_name_when_name_omitted(): void
    {
        $this->artisan('app:token-type:create', ['slug' => 'custom_type'])
            ->assertSuccessful();

        $this->assertDatabaseHas('token_types', [
            'slug' => 'custom_type',
            'name' => 'custom_type',
        ]);
    }
}
