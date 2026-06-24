<?php

namespace Tests\Feature\Console;

use App\Models\Income;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesWbTestSchema;
use Tests\TestCase;

class ImportIncomesCommandTest extends TestCase
{
    use CreatesWbTestSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createWbDataTables();
    }

    public function test_imports_incomes_for_account_with_manual_date_range(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        Http::fake([
            'test-api.local/api/incomes*' => Http::response([
                'data' => [[
                    'incomeId' => 42,
                    'supplierArticle' => 'SKU-1',
                    'barcode' => '999',
                    'techSize' => 'XL',
                    'date' => '2024-03-01',
                    'warehouseName' => 'Main',
                    'quantity' => 5,
                    'totalPrice' => 50,
                ]],
                'meta' => ['last_page' => 1],
            ], 200),
        ]);

        $this->artisan('app:import-incomes', [
            '--account' => (string) $ctx['account']->id,
            '--date-from' => '2024-01-01',
            '--date-to' => '2024-12-31',
        ])->assertSuccessful();

        $this->assertSame(1, Income::query()->where('account_id', $ctx['account']->id)->count());
        $this->assertSame(42, (int) Income::query()->value('income_id'));
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

        $this->artisan('app:import-incomes', [
            '--account' => (string) $account->id,
            '--date-from' => '2024-01-01',
            '--date-to' => '2024-12-31',
        ]);
    }
}
