<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsAccountImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportAll extends Command
{
    use RunsAccountImport;

    protected $signature = 'app:import-all
                            {--account= : ID или имя аккаунта}
                            {--all-accounts : Импорт для всех активных аккаунтов}
                            {--date-from= : Дата начала (Y-m-d), переопределяет «свежие данные»}
                            {--date-to= : Дата окончания (Y-m-d)}';

    protected $description = 'Импорт incomes, orders, sales, stocks для одного или всех аккаунтов';

    public function handle(): int
    {
        $accounts = $this->resolveAccounts();

        foreach ($accounts as $account) {
            $this->info("=== Аккаунт: {$account->name} (id={$account->id}) ===");

            $options = array_filter([
                '--account' => (string) $account->id,
                '--date-from' => $this->option('date-from'),
                '--date-to' => $this->option('date-to'),
            ], fn ($v) => $v !== null && $v !== '');

            if ($this->output->isVerbose()) {
                $options['-v'] = true;
            }

            foreach (['app:import-incomes', 'app:import-orders', 'app:import-sales', 'app:import-stocks'] as $command) {
                $exitCode = Artisan::call($command, $options, $this->output);

                if ($exitCode !== self::SUCCESS) {
                    $this->error("Команда {$command} завершилась с ошибкой для аккаунта {$account->name}.");

                    return self::FAILURE;
                }
            }
        }

        $this->info('Полный импорт завершён.');

        return self::SUCCESS;
    }
}
