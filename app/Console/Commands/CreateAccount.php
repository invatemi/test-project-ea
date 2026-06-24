<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Console\Command;

class CreateAccount extends Command
{
    protected $signature = 'app:account:create
                            {company_id : ID компании}
                            {name : Название аккаунта}';

    protected $description = 'Создать аккаунт для компании';

    public function handle(): int
    {
        $company = Company::query()->find($this->argument('company_id'));

        if ($company === null) {
            $this->error('Компания не найдена.');

            return self::FAILURE;
        }

        $account = Account::query()->create([
            'company_id' => $company->id,
            'name' => $this->argument('name'),
            'is_active' => true,
        ]);

        $this->info("Аккаунт создан: id={$account->id}, company={$company->name}, name={$account->name}");

        return self::SUCCESS;
    }
}
