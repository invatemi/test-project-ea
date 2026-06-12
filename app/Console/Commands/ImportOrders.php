<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesImportDateRange;
use App\Services\WbDataImporter;
use Illuminate\Console\Command;

class ImportOrders extends Command
{
    use ResolvesImportDateRange;

    protected $signature = 'app:import-orders
                            {--date-from= : Дата начала (Y-m-d)}
                            {--date-to= : Дата окончания (Y-m-d)}';

    protected $description = 'Импорт заказов (orders) из WB API';

    public function handle(WbDataImporter $importer): int
    {
        $dateFrom = $this->resolveDateFrom();
        $dateTo = $this->resolveDateTo();

        $this->info("Импорт orders: {$dateFrom} — {$dateTo}");

        $count = $importer->importOrders($dateFrom, $dateTo);

        $this->info("Готово: {$count} записей.");

        return self::SUCCESS;
    }
}
