<?php

namespace Tests\Unit\Services;

use App\Models\AccountSyncState;
use App\Models\Income;
use App\Models\Order;
use App\Models\Sale;
use App\Services\WbDataImporter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesWbTestSchema;
use Tests\TestCase;

class WbDataImporterTest extends TestCase
{
    use CreatesWbTestSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createWbDataTables();
    }

    public function test_throws_when_for_account_not_called(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('forAccount');

        app(WbDataImporter::class)->importOrders('2024-01-01', '2024-01-31');
    }

    public function test_import_orders_assigns_account_id_and_upserts_by_srid(): void
    {
        $ctxA = $this->seedAccountWithToken(1);
        $ctxB = $this->seedAccountWithToken(2);

        Http::fake([
            'test-api.local/api/orders*' => Http::sequence()
                ->push([
                    'data' => [[
                        'srid' => 'SR-100',
                        'date' => '2024-06-01 10:00:00',
                        'gNumber' => 'G1',
                        'totalPrice' => 100,
                    ]],
                    'meta' => ['last_page' => 1],
                ], 200)
                ->push([
                    'data' => [[
                        'srid' => 'SR-100',
                        'date' => '2024-06-01 11:00:00',
                        'gNumber' => 'G1-updated',
                        'totalPrice' => 150,
                    ]],
                    'meta' => ['last_page' => 1],
                ], 200)
                ->push([
                    'data' => [[
                        'srid' => 'SR-200',
                        'date' => '2024-06-02 10:00:00',
                        'gNumber' => 'G2',
                    ]],
                    'meta' => ['last_page' => 1],
                ], 200),
        ]);

        $importer = app(WbDataImporter::class);

        $count = $importer->forAccount($ctxA['account'], $ctxA['token'])->importOrders('2024-01-01', '2024-06-30');
        $this->assertSame(1, $count);
        $this->assertSame('G1', Order::query()->where('account_id', $ctxA['account']->id)->value('g_number'));

        $count = $importer->forAccount($ctxA['account'], $ctxA['token'])->importOrders('2024-01-01', '2024-06-30');
        $this->assertSame(1, $count);
        $this->assertSame(1, Order::query()->where('account_id', $ctxA['account']->id)->count());
        $this->assertSame('G1-updated', Order::query()->where('account_id', $ctxA['account']->id)->value('g_number'));

        $importer->forAccount($ctxB['account'], $ctxB['token'])->importOrders('2024-01-01', '2024-06-30');

        $this->assertSame(1, Order::query()->where('account_id', $ctxA['account']->id)->count());
        $this->assertSame(1, Order::query()->where('account_id', $ctxB['account']->id)->count());
        $this->assertSame('G1-updated', Order::query()->where('account_id', $ctxA['account']->id)->value('g_number'));
        $this->assertSame('G2', Order::query()->where('account_id', $ctxB['account']->id)->value('g_number'));
        $this->assertSame('SR-200', Order::query()->where('account_id', $ctxB['account']->id)->value('srid'));
    }

    public function test_import_incomes_paginates_multiple_pages(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        Http::fake([
            'test-api.local/api/incomes*' => Http::sequence()
                ->push([
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
                    'meta' => ['last_page' => 2],
                ], 200)
                ->push([
                    'data' => [[
                        'incomeId' => 2,
                        'supplierArticle' => 'ART-2',
                        'barcode' => '222',
                        'techSize' => 'L',
                        'date' => '2024-01-02',
                        'warehouseName' => 'WH',
                        'quantity' => 2,
                        'totalPrice' => 20,
                    ]],
                    'meta' => ['last_page' => 2],
                ], 200),
        ]);

        $count = app(WbDataImporter::class)
            ->forAccount($ctx['account'], $ctx['token'])
            ->import(
                endpoint: 'incomes',
                model: new Income,
                fillable: (new Income)->getFillable(),
                dateFrom: '2024-01-01',
                dateTo: '2024-12-31',
                uniqueBy: ['income_id', 'supplier_article', 'barcode', 'tech_size'],
            );

        $this->assertSame(2, $count);
        $this->assertSame(2, Income::query()->where('account_id', $ctx['account']->id)->count());
    }

    public function test_import_sales_skips_rows_without_sale_id(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        Http::fake([
            'test-api.local/api/sales*' => Http::response([
                'data' => [
                    ['saleId' => 'S1', 'date' => '2024-01-01'],
                    ['date' => '2024-01-02'],
                    ['saleId' => '', 'date' => '2024-01-03'],
                ],
                'meta' => ['last_page' => 1],
            ], 200),
        ]);

        $count = app(WbDataImporter::class)
            ->forAccount($ctx['account'], $ctx['token'])
            ->import(
                endpoint: 'sales',
                model: new Sale,
                fillable: (new Sale)->getFillable(),
                dateFrom: '2024-01-01',
                dateTo: '2024-12-31',
                uniqueBy: ['sale_id'],
                rowTransformer: fn (array $record) => empty($record['sale_id']) ? [] : $record,
            );

        $this->assertSame(1, $count);
    }

    public function test_mark_synced_persists_account_sync_state(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        app(WbDataImporter::class)
            ->forAccount($ctx['account'], $ctx['token'])
            ->markSynced('incomes', '2024-06-01');

        $state = AccountSyncState::query()
            ->where('account_id', $ctx['account']->id)
            ->where('entity', 'incomes')
            ->first();

        $this->assertNotNull($state);
        $this->assertSame('2024-06-01', $state->last_date_from->format('Y-m-d'));
    }

    public function test_stock_date_normalizes_input(): void
    {
        Carbon::setTestNow('2025-03-15 12:00:00');

        $this->assertSame('2025-03-15', WbDataImporter::stockDate());
        $this->assertSame('2024-12-01', WbDataImporter::stockDate('2024-12-01'));

        Carbon::setTestNow();
    }

    public function test_import_throws_on_api_failure(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        Http::fake([
            'test-api.local/api/incomes*' => Http::response(['error' => 'fail'], 500),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        app(WbDataImporter::class)
            ->forAccount($ctx['account'], $ctx['token'])
            ->import(
                endpoint: 'incomes',
                model: new Income,
                fillable: (new Income)->getFillable(),
                dateFrom: '2024-01-01',
                dateTo: '2024-12-31',
                uniqueBy: ['income_id', 'supplier_article', 'barcode', 'tech_size'],
            );
    }

    public function test_import_rolls_back_when_later_page_fails(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        Http::fake([
            'test-api.local/api/incomes*' => Http::sequence()
                ->push([
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
                    'meta' => ['last_page' => 2],
                ], 200)
                ->push(['error' => 'fail'], 500),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        try {
            app(WbDataImporter::class)
                ->forAccount($ctx['account'], $ctx['token'])
                ->import(
                    endpoint: 'incomes',
                    model: new Income,
                    fillable: (new Income)->getFillable(),
                    dateFrom: '2024-01-01',
                    dateTo: '2024-12-31',
                    uniqueBy: ['income_id', 'supplier_article', 'barcode', 'tech_size'],
                );
        } finally {
            $this->assertSame(0, Income::query()->where('account_id', $ctx['account']->id)->count());
        }
    }
}
