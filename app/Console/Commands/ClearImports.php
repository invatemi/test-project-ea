<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearImports extends Command
{
    protected $signature = 'app:clear-imports';

    protected $description = 'Очистить импортированные данные (incomes, orders, sales, stocks)';

    public function handle(): int
    {
        $isMysql = DB::connection()->getDriverName() === 'mysql';

        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        foreach (['incomes', 'orders', 'sales', 'stocks'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('Таблицы incomes, orders, sales, stocks очищены.');

        return self::SUCCESS;
    }
}
