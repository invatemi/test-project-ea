<?php

namespace Tests\Feature\Console;

use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesWbTestSchema;
use Tests\TestCase;

class ImportStocksCommandTest extends TestCase
{
    use CreatesWbTestSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createWbDataTables();
    }

    public function test_imports_stocks_for_specific_date(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        Http::fake([
            'test-api.local/api/stocks*' => Http::response([
                'data' => [[
                    'nmId' => 12345,
                    'warehouseName' => 'Main',
                    'barcode' => '999',
                    'techSize' => 'M',
                    'quantity' => 10,
                ]],
                'meta' => ['last_page' => 1],
            ], 200),
        ]);

        $this->artisan('app:import-stocks', [
            '--account' => (string) $ctx['account']->id,
            '--date' => '2024-06-15',
        ])->assertSuccessful();

        $stock = Stock::query()->where('account_id', $ctx['account']->id)->first();

        $this->assertNotNull($stock);
        $this->assertSame('2024-06-15', $stock->date->format('Y-m-d'));
        $this->assertSame(12345, (int) $stock->nm_id);
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

        $this->artisan('app:import-stocks', [
            '--account' => (string) $account->id,
            '--date' => '2024-06-15',
        ]);
    }
}
