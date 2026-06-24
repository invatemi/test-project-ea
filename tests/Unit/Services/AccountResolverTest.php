<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\AccountToken;
use App\Models\ApiService;
use App\Models\Company;
use App\Models\TokenType;
use App\Services\AccountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountResolverTest extends TestCase
{
    use RefreshDatabase;

    private function seedApiStructure(): array
    {
        $this->artisan('migrate');

        $company = Company::query()->create(['name' => 'Co']);
        $accountA = Account::query()->create(['company_id' => $company->id, 'name' => 'alpha', 'is_active' => true]);
        $accountB = Account::query()->create(['company_id' => $company->id, 'name' => 'beta', 'is_active' => true]);

        $service = ApiService::query()->firstOrCreate(
            ['slug' => 'wb_test'],
            ['name' => 'WB', 'base_url' => config('wb_api.host') ?? 'http://test-api.local'],
        );

        $type = TokenType::query()->firstOrCreate(
            ['slug' => 'api_key'],
            ['name' => 'API Key'],
        );
        $service->tokenTypes()->syncWithoutDetaching([$type->id]);

        foreach ([$accountA, $accountB] as $acc) {
            $token = AccountToken::query()->firstOrNew([
                'account_id' => $acc->id,
                'api_service_id' => $service->id,
                'token_type_id' => $type->id,
            ]);
            $token->is_active = true;
            $token->setCredentialsArray(['key' => 'key-'.$acc->id]);
            $token->save();
        }

        return compact('accountA', 'accountB', 'service', 'type');
    }

    public function test_all_accounts_returns_only_active_with_tokens(): void
    {
        ['accountA' => $a, 'accountB' => $b] = $this->seedApiStructure();

        $accounts = app(AccountResolver::class)->resolve(null, true);

        $this->assertCount(2, $accounts);
        $this->assertTrue($accounts->pluck('id')->contains($a->id));
        $this->assertTrue($accounts->pluck('id')->contains($b->id));
    }

    public function test_resolve_by_name_returns_single_account(): void
    {
        ['accountA' => $a] = $this->seedApiStructure();

        $accounts = app(AccountResolver::class)->resolve('alpha', false);

        $this->assertCount(1, $accounts);
        $this->assertSame($a->id, $accounts->first()->id);
    }

    public function test_get_token_returns_decrypted_credentials(): void
    {
        ['accountA' => $a] = $this->seedApiStructure();

        $token = app(AccountResolver::class)->getToken($a);

        $this->assertSame(['key' => 'key-'.$a->id], $token->getCredentialsArray());
    }

    public function test_throws_when_account_has_no_token_and_no_env_fallback(): void
    {
        $this->artisan('migrate');
        config(['wb_api.key' => null]);

        $company = Company::query()->create(['name' => 'Empty']);
        $account = Account::query()->create(['company_id' => $company->id, 'name' => 'no-token', 'is_active' => true]);
        ApiService::query()->firstOrCreate(
            ['slug' => 'wb_test'],
            ['name' => 'WB', 'base_url' => 'http://x'],
        );

        $this->expectException(\InvalidArgumentException::class);

        app(AccountResolver::class)->getToken($account);
    }
}
