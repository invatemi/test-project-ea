<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsAccountImport;
use App\Services\ImportRunner;
use App\Services\WbDataImporter;
use Illuminate\Console\Command;

class ImportStocks extends Command
{
    use RunsAccountImport;

    protected $signature = 'app:import-stocks
                            {--account= : ID или имя аккаунта}
                            {--all-accounts : Импорт для всех активных аккаунтов}
                            {--date= : Дата среза остатков (Y-m-d), по умолчанию сегодня}
                            {--queue : Поставить импорт в очередь}';

    protected $description = 'Импорт остатков (stocks) из WB API';

    public function handle(ImportRunner $runner): int
    {
        if ($this->option('queue')) {
            return $this->dispatchQueuedEntities(['stocks']);
        }

        $total = 0;

        foreach ($this->resolveAccounts() as $account) {
            $date = $this->option('date')
                ? WbDataImporter::stockDate($this->option('date'))
                : $runner->resolveRange('stocks', $account, null, null)['date_from'];

            $this->info("Импорт stocks [{$account->name}] за {$date}");

            $count = $runner->runEntity(
                entity: 'stocks',
                account: $account,
                stockDate: $this->option('date'),
                verbose: $this->output->isVerbose(),
                debugLogger: $this->debugLogger(),
            );

            $this->info("Готово [{$account->name}]: {$count} записей.");
            $total += $count;
        }

        $this->info("Итого stocks: {$total} записей.");

        return self::SUCCESS;
    }
}
