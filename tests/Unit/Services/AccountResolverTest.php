<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\AccountToken;
use App\Models\ApiService;
use App\Models\Company;
use App\Models\TokenType;
use App\Services\AccountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesWbTestSchema;
use Tests\TestCase;

class AccountResolverTest extends TestCase
{
    use CreatesWbTestSchema;
    use RefreshDatabase;

    public function test_all_accounts_returns_only_active_with_tokens(): void
    {
        $ctxA = $this->seedAccountWithToken(1);
        $ctxB = $this->seedAccountWithToken(2);

        $accounts = app(AccountResolver::class)->resolve(null, true);

        $this->assertCount(2, $accounts);
        $this->assertTrue($accounts->pluck('id')->contains($ctxA['account']->id));
        $this->assertTrue($accounts->pluck('id')->contains($ctxB['account']->id));
    }

    public function test_resolve_by_name_returns_single_account(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        $accounts = app(AccountResolver::class)->resolve('account-1', false);

        $this->assertCount(1, $accounts);
        $this->assertSame($ctx['account']->id, $accounts->first()->id);
    }

    public function test_get_token_returns_decrypted_credentials(): void
    {
        $ctx = $this->seedAccountWithToken(1, 'unique-key-abc');

        $token = app(AccountResolver::class)->getToken($ctx['account']);

        $this->assertSame(['key' => 'unique-key-abc'], $token->getCredentialsArray());
    }

    public function test_throws_when_account_has_no_token_and_no_env_fallback(): void
    {
        config(['wb_api.key' => null]);

        $company = Company::query()->create(['name' => 'Empty']);
        $account = Account::query()->create(['company_id' => $company->id, 'name' => 'no-token', 'is_active' => true]);
        ApiService::query()->firstOrCreate(['slug' => 'wb_test'], ['name' => 'WB', 'base_url' => 'http://x']);

        $this->expectException(\InvalidArgumentException::class);

        app(AccountResolver::class)->getToken($account);
    }

    public function test_throws_when_multiple_accounts_without_explicit_selection(): void
    {
        $this->seedAccountWithToken(1);
        $this->seedAccountWithToken(2);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('--account=');

        app(AccountResolver::class)->resolve(null, false);
    }

    public function test_legacy_env_key_fallback_when_no_db_token(): void
    {
        config(['wb_api.key' => 'legacy-env-key']);

        $company = Company::query()->create(['name' => 'Legacy Co']);
        $account = Account::query()->create(['company_id' => $company->id, 'name' => 'plain', 'is_active' => true]);
        $service = ApiService::query()->firstOrCreate(['slug' => 'wb_test'], ['name' => 'WB', 'base_url' => 'http://x']);
        $type = TokenType::query()->firstOrCreate(['slug' => 'api_key'], ['name' => 'Key']);
        $service->tokenTypes()->syncWithoutDetaching([$type->id]);

        $token = app(AccountResolver::class)->getToken($account);

        $this->assertSame(['key' => 'legacy-env-key'], $token->getCredentialsArray());
        $this->assertNull($token->id);
    }
}
