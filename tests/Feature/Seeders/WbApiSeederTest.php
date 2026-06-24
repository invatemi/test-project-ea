<?php

namespace Tests\Feature\Seeders;

use App\Models\Account;
use App\Models\AccountToken;
use App\Models\ApiService;
use App\Models\TokenType;
use Database\Seeders\WbApiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WbApiSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_api_service_from_env_host(): void
    {
        config(['wb_api.host' => 'http://seed-host.local', 'wb_api.key' => null]);

        $this->seed(WbApiSeeder::class);

        $this->assertDatabaseHas('api_services', [
            'slug' => 'wb_test',
            'base_url' => 'http://seed-host.local',
        ]);
        $this->assertDatabaseHas('token_types', ['slug' => 'api_key']);
        $this->assertDatabaseHas('companies', ['name' => 'Legacy']);
        $this->assertDatabaseHas('accounts', ['name' => 'default']);
    }

    public function test_seeds_account_token_when_wb_api_key_in_env(): void
    {
        config(['wb_api.host' => 'http://seed-host.local', 'wb_api.key' => 'env-secret-key']);

        $this->seed(WbApiSeeder::class);

        $account = Account::query()->where('name', 'default')->first();
        $service = ApiService::findBySlug('wb_test');
        $type = TokenType::findBySlug('api_key');

        $token = AccountToken::query()
            ->where('account_id', $account->id)
            ->where('api_service_id', $service->id)
            ->where('token_type_id', $type->id)
            ->first();

        $this->assertNotNull($token);
        $this->assertSame(['key' => 'env-secret-key'], $token->getCredentialsArray());
    }

    public function test_skips_api_service_update_when_host_not_configured(): void
    {
        config(['wb_api.host' => null, 'wb_api.key' => null]);

        ApiService::query()->where('slug', 'wb_test')->delete();

        $this->seed(WbApiSeeder::class);

        $this->assertNull(ApiService::findBySlug('wb_test'));
    }
}
