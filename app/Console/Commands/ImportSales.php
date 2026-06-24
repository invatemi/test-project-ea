<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsAccountImport;
use App\Models\Sale;
use App\Services\WbDataImporter;
use Illuminate\Console\Command;

class ImportSales extends Command
{
    use RunsAccountImport;

    protected $signature = 'app:import-sales
                            {--account= : ID или имя аккаунта}
                            {--all-accounts : Импорт для всех активных аккаунтов}
                            {--date-from= : Дата начала (Y-m-d)}
                            {--date-to= : Дата окончания (Y-m-d)}';

    protected $description = 'Импорт продаж (sales) из WB API';

    public function handle(WbDataImporter $importer): int
    {
        $importer = $this->configureImporter();
        $total = 0;

        foreach ($this->resolveAccounts() as $account) {
            $range = $this->resolveFreshRange('sales', $account);
            $dateFrom = $range['date_from'];
            $dateTo = $range['date_to'];

            $this->info("Импорт sales [{$account->name}]: {$dateFrom} — {$dateTo}");

            $count = $this->importerForAccount($importer, $account)->import(
                endpoint: 'sales',
                model: new Sale,
                fillable: (new Sale)->getFillable(),
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                uniqueBy: ['sale_id'],
                rowTransformer: fn (array $record) => empty($record['sale_id']) ? [] : $record,
            );

            $this->importerForAccount($importer, $account)->markSynced('sales', $dateFrom);
            $this->info("Готово [{$account->name}]: {$count} записей.");
            $total += $count;
        }

        $this->info("Итого sales: {$total} записей.");

        return self::SUCCESS;
    }
}
