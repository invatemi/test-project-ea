<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsAccountImport;
use App\Services\ImportRunner;
use Illuminate\Console\Command;

class ImportIncomes extends Command
{
    use RunsAccountImport;

    protected $signature = 'app:import-incomes
                            {--account= : ID или имя аккаунта}
                            {--all-accounts : Импорт для всех активных аккаунтов}
                            {--date-from= : Дата начала (Y-m-d)}
                            {--date-to= : Дата окончания (Y-m-d)}
                            {--queue : Поставить импорт в очередь}';

    protected $description = 'Импорт поставок (incomes) из WB API';

    public function handle(ImportRunner $runner): int
    {
        if ($this->option('queue')) {
            return $this->dispatchQueuedEntities(['incomes']);
        }

        $total = 0;

        foreach ($this->resolveAccounts() as $account) {
            $range = $runner->resolveRange('incomes', $account, $this->option('date-from'), $this->option('date-to'));
            $this->logFreshRange('incomes', $account);
            $this->info("Импорт incomes [{$account->name}]: {$range['date_from']} — {$range['date_to']}");

            $count = $runner->runEntity(
                entity: 'incomes',
                account: $account,
                dateFrom: $this->option('date-from'),
                dateTo: $this->option('date-to'),
                verbose: $this->output->isVerbose(),
                debugLogger: $this->debugLogger(),
            );

            $this->info("Готово [{$account->name}]: {$count} записей.");
            $total += $count;
        }

        $this->info("Итого incomes: {$total} записей.");

        return self::SUCCESS;
    }
}
