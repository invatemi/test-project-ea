<?php

namespace Tests\Feature\Console;

use App\Models\Income;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesWbTestSchema;
use Tests\TestCase;

class ClearImportsCommandTest extends TestCase
{
    use CreatesWbTestSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createWbDataTables();
    }

    public function test_truncates_all_import_tables(): void
    {
        $ctx = $this->seedAccountWithToken(1);

        Income::query()->create([
            'account_id' => $ctx['account']->id,
            'income_id' => 1,
            'supplier_article' => 'A',
            'barcode' => '1',
            'tech_size' => 'M',
            'date' => '2024-01-01',
            'warehouse_name' => 'WH',
            'quantity' => 1,
            'total_price' => 1,
        ]);

        Order::query()->create([
            'account_id' => $ctx['account']->id,
            'srid' => 'SR1',
            'date' => '2024-01-01',
            'is_cancel' => false,
        ]);

        $this->assertSame(1, Income::count());
        $this->assertSame(1, Order::count());

        $this->artisan('app:clear-imports')->assertSuccessful();

        $this->assertSame(0, Income::count());
        $this->assertSame(0, Order::count());
    }
}
