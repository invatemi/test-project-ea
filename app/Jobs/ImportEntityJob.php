<?php

namespace App\Jobs;

use App\Models\Account;
use App\Services\ImportRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportEntityJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public int $accountId,
        public string $entity,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $stockDate = null,
    ) {
    }

    public function handle(ImportRunner $runner): void
    {
        $account = Account::query()->findOrFail($this->accountId);

        $runner->runEntity(
            entity: $this->entity,
            account: $account,
            dateFrom: $this->dateFrom,
            dateTo: $this->dateTo,
            stockDate: $this->stockDate,
        );
    }
}
