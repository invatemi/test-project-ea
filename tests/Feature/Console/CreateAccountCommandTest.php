<?php

namespace Tests\Feature\Console;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAccountCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_account_for_company(): void
    {
        $company = Company::query()->create(['name' => 'Parent Co']);

        $this->artisan('app:account:create', [
            'company_id' => $company->id,
            'name' => 'WB Main',
        ])->assertSuccessful();

        $this->assertDatabaseHas('accounts', [
            'company_id' => $company->id,
            'name' => 'WB Main',
            'is_active' => 1,
        ]);
    }

    public function test_fails_when_company_not_found(): void
    {
        $this->artisan('app:account:create', [
            'company_id' => 9999,
            'name' => 'Orphan',
        ])->assertFailed();
    }
}
