<?php

namespace Tests\Feature\Console;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesWbTestSchema;
use Tests\TestCase;

class ImportOrdersCommandTest extends TestCase
{
    use CreatesWbTestSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createWbDataTables();
    }

    public function test_imports_orders_for_account_with_manual_date_range(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        Http::fake([
            'test-api.local/api/orders*' => Http::response([
                'data' => [[
                    'srid' => 'SR-100',
                    'date' => '2024-06-01 10:00:00',
                    'gNumber' => 'G1',
                    'totalPrice' => 100,
                ]],
                'meta' => ['last_page' => 1],
            ], 200),
        ]);

        $this->artisan('app:import-orders', [
            '--account' => (string) $ctx['account']->id,
            '--date-from' => '2024-01-01',
            '--date-to' => '2024-12-31',
        ])->assertSuccessful();

        $this->assertSame(1, Order::query()->where('account_id', $ctx['account']->id)->count());
        $this->assertSame('SR-100', Order::query()->value('srid'));
    }

    public function test_fails_when_account_has_no_token(): void
    {
        config(['wb_api.key' => null]);

        $company = \App\Models\Company::query()->create(['name' => 'Lonely Co']);
        $account = \App\Models\Account::query()->create([
            'company_id' => $company->id,
            'name' => 'no-token',
            'is_active' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->artisan('app:import-orders', [
            '--account' => (string) $account->id,
            '--date-from' => '2024-01-01',
            '--date-to' => '2024-12-31',
        ]);
    }
}
