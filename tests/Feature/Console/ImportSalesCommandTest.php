<?php

namespace Tests\Feature\Console;

use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesWbTestSchema;
use Tests\TestCase;

class ImportSalesCommandTest extends TestCase
{
    use CreatesWbTestSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createWbDataTables();
    }

    public function test_imports_sales_and_skips_rows_without_sale_id(): void
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

        $this->artisan('app:import-sales', [
            '--account' => (string) $ctx['account']->id,
            '--date-from' => '2024-01-01',
            '--date-to' => '2024-12-31',
        ])->assertSuccessful();

        $this->assertSame(1, Sale::query()->where('account_id', $ctx['account']->id)->count());
        $this->assertSame('S1', Sale::query()->value('sale_id'));
    }

    public function test_imports_sales_with_null_is_storno(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        Http::fake([
            'test-api.local/api/sales*' => Http::response([
                'data' => [
                    ['saleId' => 'S1', 'date' => '2024-01-01', 'isStorno' => null],
                ],
                'meta' => ['last_page' => 1],
            ], 200),
        ]);

        $this->artisan('app:import-sales', [
            '--account' => (string) $ctx['account']->id,
            '--date-from' => '2024-01-01',
            '--date-to' => '2024-12-31',
        ])->assertSuccessful();

        $this->assertFalse(Sale::query()->where('sale_id', 'S1')->value('is_storno'));
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

        $this->artisan('app:import-sales', [
            '--account' => (string) $account->id,
            '--date-from' => '2024-01-01',
            '--date-to' => '2024-12-31',
        ]);
    }
}
