<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Services\WbDataImporter;
use Illuminate\Console\Command;

class ImportStocks extends Command
{
    protected $signature = 'app:import-stocks
                            {--date= : Дата среза остатков (Y-m-d), по умолчанию сегодня}';

    protected $description = 'Импорт остатков (stocks) из WB API';

    public function handle(WbDataImporter $importer): int
    {
        $date = WbDataImporter::stockDate($this->option('date'));

        $this->info("Импорт stocks за {$date}");

        $count = $importer->import(
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

        $this->info("Готово: {$count} записей.");

        return self::SUCCESS;
    }
}
