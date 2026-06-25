<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsAccountImport;
use App\Services\ImportRunner;
use Illuminate\Console\Command;

class ImportAll extends Command
{
    use RunsAccountImport;

    /** @var array<int, string> */
    private const ENTITIES = ['incomes', 'orders', 'sales', 'stocks'];

    protected $signature = 'app:import-all
                            {--account= : ID или имя аккаунта}
                            {--all-accounts : Импорт для всех активных аккаунтов}
                            {--date-from= : Дата начала (Y-m-d), переопределяет «свежие данные»}
                            {--date-to= : Дата окончания (Y-m-d)}
                            {--queue : Поставить импорт в очередь}';

    protected $description = 'Импорт incomes, orders, sales, stocks для одного или всех аккаунтов';

    public function handle(ImportRunner $runner): int
    {
        if ($this->option('queue')) {
            if ($this->option('all-accounts') && ! $this->option('account')) {
                return $this->dispatchImportAllJob();
            }

            return $this->dispatchQueuedEntities(self::ENTITIES);
        }

        foreach ($this->resolveAccounts() as $account) {
            $this->info("=== Аккаунт: {$account->name} (id={$account->id}) ===");

            foreach (self::ENTITIES as $entity) {
                try {
                    $count = $runner->runEntity(
                        entity: $entity,
                        account: $account,
                        dateFrom: $this->option('date-from'),
                        dateTo: $this->option('date-to'),
                        verbose: $this->output->isVerbose(),
                        debugLogger: $this->debugLogger(),
                    );

                    $this->info("  {$entity}: {$count} записей.");
                } catch (\Throwable $e) {
                    $this->error("  {$entity} завершился с ошибкой: {$e->getMessage()}");

                    return self::FAILURE;
                }
            }
        }

        $this->info('Полный импорт завершён.');

        return self::SUCCESS;
    }
}
