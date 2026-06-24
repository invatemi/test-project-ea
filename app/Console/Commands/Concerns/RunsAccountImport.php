<?php

namespace App\Console\Commands\Concerns;

use App\Models\Account;
use App\Models\AccountToken;
use App\Services\AccountResolver;
use App\Services\FreshDataRangeResolver;
use App\Services\WbDataImporter;

trait RunsAccountImport
{
    protected function configureImporter(): WbDataImporter
    {
        $importer = app(WbDataImporter::class)->setVerbose($this->output->isVerbose());

        $importer->setDebugLogger(function (string $message): void {
            if ($this->output->isVerbose()) {
                $this->line($message);
            }
        });

        return $importer;
    }

    /** @return \Illuminate\Support\Collection<int, Account> */
    protected function resolveAccounts(): \Illuminate\Support\Collection
    {
        return app(AccountResolver::class)->resolve(
            $this->option('account'),
            (bool) $this->option('all-accounts'),
        );
    }

    protected function importerForAccount(WbDataImporter $importer, Account $account): WbDataImporter
    {
        $token = app(AccountResolver::class)->getToken($account);

        return $importer->forAccount($account, $token);
    }

    protected function resolveFreshRange(string $entity, Account $account): array
    {
        $useFresh = ! $this->option('date-from') && ! $this->option('date-to');

        if (! $useFresh) {
            return [
                'date_from' => $this->option('date-from') ?? config('wb_api.date_from'),
                'date_to' => $this->option('date-to') ?? config('wb_api.date_to'),
            ];
        }

        $range = app(FreshDataRangeResolver::class)->resolve(
            $entity,
            $account->id,
            null,
            null,
        );

        if ($range['fresh'] && $this->output->isVerbose()) {
            $this->line("Свежие данные: {$range['date_from']} — {$range['date_to']}");
        }

        return $range;
    }
}
