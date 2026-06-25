<?php

namespace App\Console\Commands\Concerns;

use App\Jobs\ImportAllAccountsJob;
use App\Jobs\ImportEntityJob;
use App\Models\Account;
use App\Services\AccountResolver;
use App\Services\FreshDataRangeResolver;
use App\Services\ImportRunner;
use App\Services\WbDataImporter;
use Illuminate\Console\Command;

trait RunsAccountImport
{
    /** @return \Illuminate\Support\Collection<int, Account> */
    protected function resolveAccounts(): \Illuminate\Support\Collection
    {
        return app(AccountResolver::class)->resolve(
            $this->option('account'),
            (bool) $this->option('all-accounts'),
        );
    }

    protected function debugLogger(): \Closure
    {
        return function (string $message): void {
            if ($this->output->isVerbose()) {
                $this->line($message);
            }
        };
    }

    protected function logFreshRange(string $entity, Account $account): void
    {
        if (! $this->output->isVerbose()) {
            return;
        }

        $range = app(ImportRunner::class)->resolveRange(
            $entity,
            $account,
            $this->option('date-from'),
            $this->option('date-to'),
        );

        if ($range['fresh']) {
            $to = $range['date_to'] ?? '—';
            $this->line("Свежие данные: {$range['date_from']} — {$to}");
        }
    }

    protected function dispatchEntityJob(string $entity, Account $account): void
    {
        ImportEntityJob::dispatch(
            accountId: $account->id,
            entity: $entity,
            dateFrom: $this->option('date-from'),
            dateTo: $this->option('date-to'),
            stockDate: $this->optionalStockDate(),
        );

        $this->info("Job поставлен в очередь: {$entity} [{$account->name}]");
    }

    protected function optionalStockDate(): ?string
    {
        return $this->input->hasOption('date') ? $this->option('date') : null;
    }

    /** @param  array<int, string>  $entities */
    protected function dispatchQueuedEntities(array $entities): int
    {
        foreach ($this->resolveAccounts() as $account) {
            foreach ($entities as $entity) {
                $this->dispatchEntityJob($entity, $account);
            }
        }

        return Command::SUCCESS;
    }

    protected function dispatchImportAllJob(): int
    {
        ImportAllAccountsJob::dispatch(
            dateFrom: $this->option('date-from'),
            dateTo: $this->option('date-to'),
            stockDate: $this->optionalStockDate(),
        );

        $this->info('Job полного импорта поставлен в очередь для всех активных аккаунтов.');

        return Command::SUCCESS;
    }
}
