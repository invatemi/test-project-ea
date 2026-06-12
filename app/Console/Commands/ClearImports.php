<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearImports extends Command
{
    protected $signature = 'app:clear-imports';

    protected $description = 'Очистить импортированные данные (incomes, orders, sales, stocks)';

    public function handle(): int
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (['incomes', 'orders', 'sales', 'stocks'] as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('Таблицы incomes, orders, sales, stocks очищены.');

        return self::SUCCESS;
    }
}
