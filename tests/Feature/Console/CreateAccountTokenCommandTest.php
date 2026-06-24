<?php

namespace Tests\Feature\Console;

use App\Models\AccountToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesWbTestSchema;
use Tests\TestCase;

class CreateAccountTokenCommandTest extends TestCase
{
    use CreatesWbTestSchema;
    use RefreshDatabase;

    public function test_creates_token_with_credentials_on_first_insert(): void
    {
        $ctx = $this->seedAccountWithToken(1);
        AccountToken::query()->where('account_id', $ctx['account']->id)->delete();

        $this->artisan('app:account-token:create', [
            'account_id' => $ctx['account']->id,
            'service_slug' => 'wb_test',
            'type_slug' => 'api_key',
            '--key' => 'my-secret-key',
        ])->assertSuccessful();

        $token = AccountToken::query()->where('account_id', $ctx['account']->id)->first();

        $this->assertNotNull($token);
        $this->assertSame(['key' => 'my-secret-key'], $token->getCredentialsArray());
    }

    public function test_rejects_token_type_not_allowed_for_service(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        $this->artisan('app:account-token:create', [
            'account_id' => $ctx['account']->id,
            'service_slug' => 'wb_test',
            'type_slug' => 'bearer',
            '--token' => 'abc',
        ])->assertFailed();
    }

    public function test_updates_existing_token_credentials(): void
    {
        $ctx = $this->seedAccountWithToken(1, 'old-key');

        $this->artisan('app:account-token:create', [
            'account_id' => $ctx['account']->id,
            'service_slug' => 'wb_test',
            'type_slug' => 'api_key',
            '--key' => 'new-key',
        ])->assertSuccessful();

        $this->assertSame(['key' => 'new-key'], $ctx['token']->fresh()->getCredentialsArray());
    }
}
