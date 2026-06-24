<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class CreateCompany extends Command
{
    protected $signature = 'app:company:create {name : Название компании}';

    protected $description = 'Создать компанию';

    public function handle(): int
    {
        $company = Company::query()->create(['name' => $this->argument('name')]);

        $this->info("Компания создана: id={$company->id}, name={$company->name}");

        return self::SUCCESS;
    }
}
