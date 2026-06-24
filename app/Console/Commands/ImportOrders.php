<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsAccountImport;
use App\Services\WbDataImporter;
use Illuminate\Console\Command;

class ImportOrders extends Command
{
    use RunsAccountImport;

    protected $signature = 'app:import-orders
                            {--account= : ID или имя аккаунта}
                            {--all-accounts : Импорт для всех активных аккаунтов}
                            {--date-from= : Дата начала (Y-m-d)}
                            {--date-to= : Дата окончания (Y-m-d)}';

    protected $description = 'Импорт заказов (orders) из WB API';

    public function handle(WbDataImporter $importer): int
    {
        $importer = $this->configureImporter();
        $total = 0;

        foreach ($this->resolveAccounts() as $account) {
            $range = $this->resolveFreshRange('orders', $account);
            $dateFrom = $range['date_from'];
            $dateTo = $range['date_to'];

            $this->info("Импорт orders [{$account->name}]: {$dateFrom} — {$dateTo}");

            $count = $this->importerForAccount($importer, $account)->importOrders($dateFrom, $dateTo);

            $this->importerForAccount($importer, $account)->markSynced('orders', $dateFrom);
            $this->info("Готово [{$account->name}]: {$count} записей.");
            $total += $count;
        }

        $this->info("Итого orders: {$total} записей.");

        return self::SUCCESS;
    }
}
