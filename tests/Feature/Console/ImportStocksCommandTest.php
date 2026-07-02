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

    public function test_imports_large_stocks_payload_in_chunks(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        $pageOne = [];
        $pageTwo = [];

        for ($i = 0; $i < 250; $i++) {
            $pageOne[] = [
                'nmId' => 10000 + $i,
                'warehouseName' => 'Main',
                'barcode' => (string) (1000 + $i),
                'techSize' => 'M',
                'quantity' => 1,
            ];
            $pageTwo[] = [
                'nmId' => 20000 + $i,
                'warehouseName' => 'Main',
                'barcode' => (string) (2000 + $i),
                'techSize' => 'M',
                'quantity' => 1,
            ];
        }

        Http::fake([
            'test-api.local/api/stocks*page=1*' => Http::response([
                'data' => $pageOne,
                'meta' => ['last_page' => 2],
            ], 200),
            'test-api.local/api/stocks*page=2*' => Http::response([
                'data' => $pageTwo,
                'meta' => ['last_page' => 2],
            ], 200),
        ]);

        $this->artisan('app:import-stocks', [
            '--account' => (string) $ctx['account']->id,
            '--date' => '2024-06-15',
        ])->assertSuccessful();

        $this->assertSame(500, Stock::query()->where('account_id', $ctx['account']->id)->count());
    }

    public function test_imports_stocks_with_null_in_way_fields(): void
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
                    'inWayToClient' => null,
                    'inWayFromClient' => null,
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
        $this->assertSame(0, (int) $stock->in_way_to_client);
        $this->assertSame(0, (int) $stock->in_way_from_client);
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
