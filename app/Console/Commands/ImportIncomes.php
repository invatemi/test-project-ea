<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsAccountImport;
use App\Models\Income;
use App\Services\WbDataImporter;
use Illuminate\Console\Command;

class ImportIncomes extends Command
{
    use RunsAccountImport;

    protected $signature = 'app:import-incomes
                            {--account= : ID или имя аккаунта}
                            {--all-accounts : Импорт для всех активных аккаунтов}
                            {--date-from= : Дата начала (Y-m-d)}
                            {--date-to= : Дата окончания (Y-m-d)}';

    protected $description = 'Импорт поставок (incomes) из WB API';

    public function handle(WbDataImporter $importer): int
    {
        $importer = $this->configureImporter();
        $total = 0;

        foreach ($this->resolveAccounts() as $account) {
            $range = $this->resolveFreshRange('incomes', $account);
            $dateFrom = $range['date_from'];
            $dateTo = $range['date_to'];

            $this->info("Импорт incomes [{$account->name}]: {$dateFrom} — {$dateTo}");

            $count = $this->importerForAccount($importer, $account)->import(
                endpoint: 'incomes',
                model: new Income,
                fillable: (new Income)->getFillable(),
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                uniqueBy: ['income_id', 'supplier_article', 'barcode', 'tech_size'],
            );

            $this->importerForAccount($importer, $account)->markSynced('incomes', $dateFrom);
            $this->info("Готово [{$account->name}]: {$count} записей.");
            $total += $count;
        }

        $this->info("Итого incomes: {$total} записей.");

        return self::SUCCESS;
    }
}
