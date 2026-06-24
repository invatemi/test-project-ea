<?php

namespace Tests\Feature\Console;

use App\Models\Company;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCompanyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_company_with_unique_name(): void
    {
        $this->artisan('app:company:create', ['name' => 'ООО Тест'])
            ->expectsOutputToContain('Компания создана')
            ->assertSuccessful();

        $this->assertDatabaseHas('companies', ['name' => 'ООО Тест']);
    }

    public function test_fails_on_duplicate_company_name(): void
    {
        Company::query()->create(['name' => 'Duplicate Co']);

        $this->expectException(UniqueConstraintViolationException::class);

        $this->artisan('app:company:create', ['name' => 'Duplicate Co']);
    }
}
