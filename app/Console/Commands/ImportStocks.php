<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsAccountImport;
use App\Models\Stock;
use App\Services\FreshDataRangeResolver;
use App\Services\WbDataImporter;
use Illuminate\Console\Command;

class ImportStocks extends Command
{
    use RunsAccountImport;

    protected $signature = 'app:import-stocks
                            {--account= : ID или имя аккаунта}
                            {--all-accounts : Импорт для всех активных аккаунтов}
                            {--date= : Дата среза остатков (Y-m-d), по умолчанию сегодня}';

    protected $description = 'Импорт остатков (stocks) из WB API';

    public function handle(WbDataImporter $importer): int
    {
        $importer = $this->configureImporter();
        $resolver = app(FreshDataRangeResolver::class);
        $total = 0;

        foreach ($this->resolveAccounts() as $account) {
            $date = $this->option('date')
                ? WbDataImporter::stockDate($this->option('date'))
                : $resolver->resolveStockDate();

            $this->info("Импорт stocks [{$account->name}] за {$date}");

            $count = $this->importerForAccount($importer, $account)->import(
                endpoint: 'stocks',
                model: new Stock,
                fillable: (new Stock)->getFillable(),
                dateFrom: $date,
                dateTo: null,
                uniqueBy: ['date', 'nm_id', 'warehouse_name', 'barcode', 'tech_size'],
                rowTransformer: function (array $record) use ($date) {
                    $record['date'] = $date;

                    return (! isset($record['nm_id']) || $record['nm_id'] === '' || empty($record['warehouse_name']))
                        ? []
                        : $record;
                },
            );

            $this->importerForAccount($importer, $account)->markSynced('stocks', $date);
            $this->info("Готово [{$account->name}]: {$count} записей.");
            $total += $count;
        }

        $this->info("Итого stocks: {$total} записей.");

        return self::SUCCESS;
    }
}
