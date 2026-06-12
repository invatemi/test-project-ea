<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesImportDateRange;
use App\Models\Sale;
use App\Services\WbDataImporter;
use Illuminate\Console\Command;

class ImportSales extends Command
{
    use ResolvesImportDateRange;

    protected $signature = 'app:import-sales
                            {--date-from= : Дата начала (Y-m-d)}
                            {--date-to= : Дата окончания (Y-m-d)}';

    protected $description = 'Импорт продаж (sales) из WB API';

    public function handle(WbDataImporter $importer): int
    {
        $dateFrom = $this->resolveDateFrom();
        $dateTo = $this->resolveDateTo();

        $this->info("Импорт sales: {$dateFrom} — {$dateTo}");

        $count = $importer->import(
            endpoint: 'sales',
            model: new Sale,
            fillable: (new Sale)->getFillable(),
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            uniqueBy: ['sale_id'],
            rowTransformer: fn (array $record) => empty($record['sale_id']) ? [] : $record,
        );

        $this->info("Готово: {$count} записей.");

        return self::SUCCESS;
    }
}
