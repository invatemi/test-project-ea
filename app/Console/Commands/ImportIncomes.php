<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesImportDateRange;
use App\Models\Income;
use App\Services\WbDataImporter;
use Illuminate\Console\Command;

class ImportIncomes extends Command
{
    use ResolvesImportDateRange;

    protected $signature = 'app:import-incomes
                            {--date-from= : Дата начала (Y-m-d)}
                            {--date-to= : Дата окончания (Y-m-d)}';

    protected $description = 'Импорт поставок (incomes) из WB API';

    public function handle(WbDataImporter $importer): int
    {
        $dateFrom = $this->resolveDateFrom();
        $dateTo = $this->resolveDateTo();

        $this->info("Импорт incomes: {$dateFrom} — {$dateTo}");

        $count = $importer->import(
            endpoint: 'incomes',
            model: new Income,
            fillable: (new Income)->getFillable(),
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            uniqueBy: ['income_id', 'supplier_article', 'barcode', 'tech_size'],
        );

        $this->info("Готово: {$count} записей.");

        return self::SUCCESS;
    }
}
