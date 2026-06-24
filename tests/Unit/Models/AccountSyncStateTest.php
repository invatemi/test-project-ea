<?php

namespace Tests\Unit\Models;

use App\Models\AccountSyncState;
use App\Models\Company;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSyncStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_synced_creates_and_updates_state(): void
    {
        $company = Company::query()->create(['name' => 'Co']);
        $account = Account::query()->create(['company_id' => $company->id, 'name' => 'a1', 'is_active' => true]);

        Carbon::setTestNow('2025-06-10 08:00:00');
        AccountSyncState::markSynced($account->id, 'orders', '2025-06-01');

        $first = AccountSyncState::query()->where('account_id', $account->id)->where('entity', 'orders')->first();
        $this->assertSame('2025-06-01', $first->last_date_from->format('Y-m-d'));

        Carbon::setTestNow('2025-06-15 08:00:00');
        AccountSyncState::markSynced($account->id, 'orders', '2025-06-10');

        $this->assertSame(1, AccountSyncState::query()->where('account_id', $account->id)->count());
        $this->assertSame('2025-06-10', $first->fresh()->last_date_from->format('Y-m-d'));

        Carbon::setTestNow();
    }

    public function test_entities_constant_lists_all_import_types(): void
    {
        $this->assertSame(['incomes', 'orders', 'sales', 'stocks'], AccountSyncState::ENTITIES);
    }
}
