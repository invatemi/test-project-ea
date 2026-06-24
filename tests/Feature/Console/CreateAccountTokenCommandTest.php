<?php

namespace Tests\Feature\Console;

use App\Models\Account;
use App\Models\AccountToken;
use App\Models\ApiService;
use App\Models\Company;
use App\Models\TokenType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAccountTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_token_with_credentials_on_first_insert(): void
    {
        $this->artisan('migrate');

        $company = Company::query()->create(['name' => 'Co']);
        $account = Account::query()->create(['company_id' => $company->id, 'name' => 'main', 'is_active' => true]);
        $service = ApiService::query()->firstOrCreate(
            ['slug' => 'wb_test'],
            ['name' => 'WB', 'base_url' => 'http://test'],
        );
        $type = TokenType::query()->firstOrCreate(['slug' => 'api_key'], ['name' => 'Key']);
        $service->tokenTypes()->syncWithoutDetaching([$type->id]);

        $this->artisan('app:account-token:create', [
            'account_id' => $account->id,
            'service_slug' => 'wb_test',
            'type_slug' => 'api_key',
            '--key' => 'my-secret-key',
        ])->assertSuccessful();

        $token = AccountToken::query()->where('account_id', $account->id)->first();

        $this->assertNotNull($token);
        $this->assertSame(['key' => 'my-secret-key'], $token->getCredentialsArray());
    }

    public function test_rejects_token_type_not_allowed_for_service(): void
    {
        $this->artisan('migrate');

        $company = Company::query()->create(['name' => 'Co']);
        $account = Account::query()->create(['company_id' => $company->id, 'name' => 'main', 'is_active' => true]);
        ApiService::query()->firstOrCreate(
            ['slug' => 'wb_test'],
            ['name' => 'WB', 'base_url' => 'http://test'],
        );
        TokenType::query()->firstOrCreate(['slug' => 'bearer'], ['name' => 'Bearer']);

        $this->artisan('app:account-token:create', [
            'account_id' => $account->id,
            'service_slug' => 'wb_test',
            'type_slug' => 'bearer',
            '--token' => 'abc',
        ])->assertFailed();
    }
}
