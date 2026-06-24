<?php

namespace Tests\Unit\Services;

use App\Models\AccountSyncState;
use App\Models\Company;
use App\Models\Account;
use App\Services\FreshDataRangeResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreshDataRangeResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2025-06-15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_manual_date_range_skips_fresh_mode(): void
    {
        $resolver = app(FreshDataRangeResolver::class);

        $result = $resolver->resolve('orders', 1, '2024-01-01', '2024-06-01');

        $this->assertFalse($result['fresh']);
        $this->assertSame('2024-01-01', $result['date_from']);
        $this->assertSame('2024-06-01', $result['date_to']);
    }

    public function test_fresh_range_uses_last_sync_with_buffer(): void
    {
        $company = Company::query()->create(['name' => 'Test Co']);
        $account = Account::query()->create(['company_id' => $company->id, 'name' => 'acc1', 'is_active' => true]);

        AccountSyncState::query()->create([
            'account_id' => $account->id,
            'entity' => 'orders',
            'last_synced_at' => now(),
            'last_date_from' => '2025-06-10',
        ]);

        $resolver = app(FreshDataRangeResolver::class);
        $result = $resolver->resolve('orders', $account->id, null, null);

        $this->assertTrue($result['fresh']);
        $this->assertSame('2025-06-09', $result['date_from']);
        $this->assertSame('2025-06-15', $result['date_to']);
    }

    public function test_fresh_range_without_sync_uses_buffer_from_today(): void
    {
        $resolver = app(FreshDataRangeResolver::class);
        $result = $resolver->resolve('incomes', 99, null, null);

        $this->assertTrue($result['fresh']);
        $this->assertSame('2025-06-14', $result['date_from']);
        $this->assertSame('2025-06-15', $result['date_to']);
    }
}
