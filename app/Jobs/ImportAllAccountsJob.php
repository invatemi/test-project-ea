<?php

namespace App\Jobs;

use App\Services\AccountResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportAllAccountsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    /** @var array<int, string> */
    private const ENTITIES = ['incomes', 'orders', 'sales', 'stocks'];

    public function __construct(
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $stockDate = null,
    ) {
    }

    public function handle(AccountResolver $accountResolver): void
    {
        $accounts = $accountResolver->resolve(null, true);

        foreach ($accounts as $account) {
            foreach (self::ENTITIES as $entity) {
                ImportEntityJob::dispatch(
                    accountId: $account->id,
                    entity: $entity,
                    dateFrom: $this->dateFrom,
                    dateTo: $this->dateTo,
                    stockDate: $this->stockDate,
                );
            }
        }
    }
}
