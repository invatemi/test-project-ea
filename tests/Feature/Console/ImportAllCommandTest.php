<?php

namespace Tests\Feature\Console;

use App\Jobs\ImportEntityJob;
use App\Models\Income;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CreatesWbTestSchema;
use Tests\TestCase;

class ImportAllCommandTest extends TestCase
{
    use CreatesWbTestSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createWbDataTables();
    }

    public function test_imports_all_entities_for_account(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        Http::fake([
            'test-api.local/api/incomes*' => Http::response([
                'data' => [[
                    'incomeId' => 1,
                    'supplierArticle' => 'ART-1',
                    'barcode' => '111',
                    'techSize' => 'M',
                    'date' => '2024-01-01',
                    'warehouseName' => 'WH',
                    'quantity' => 1,
                    'totalPrice' => 10,
                ]],
                'meta' => ['last_page' => 1],
            ], 200),
            'test-api.local/api/orders*' => Http::response([
                'data' => [[
                    'srid' => 'SR-1',
                    'date' => '2024-01-01',
                    'gNumber' => 'G1',
                ]],
                'meta' => ['last_page' => 1],
            ], 200),
            'test-api.local/api/sales*' => Http::response([
                'data' => [['saleId' => 'S1', 'date' => '2024-01-01']],
                'meta' => ['last_page' => 1],
            ], 200),
            'test-api.local/api/stocks*' => Http::response([
                'data' => [[
                    'nmId' => 100,
                    'warehouseName' => 'WH',
                    'quantity' => 5,
                ]],
                'meta' => ['last_page' => 1],
            ], 200),
        ]);

        $this->artisan('app:import-all', [
            '--account' => (string) $ctx['account']->id,
            '--date-from' => '2024-01-01',
            '--date-to' => '2024-12-31',
        ])->assertSuccessful();

        $this->assertSame(1, Income::query()->where('account_id', $ctx['account']->id)->count());
        $this->assertSame(1, Order::query()->where('account_id', $ctx['account']->id)->count());
    }

    public function test_queue_flag_dispatches_entity_jobs(): void
    {
        Queue::fake();

        $ctx = $this->seedAccountWithToken(1);

        $this->artisan('app:import-all', [
            '--account' => (string) $ctx['account']->id,
            '--queue' => true,
        ])->assertSuccessful();

        Queue::assertPushed(ImportEntityJob::class, 4);
    }
}
