<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Income;
use App\Models\Sale;
use App\Models\Stock;

class ImportRunner
{
    public function __construct(
        private readonly AccountResolver $accountResolver,
        private readonly FreshDataRangeResolver $rangeResolver,
        private readonly WbDataImporter $importer,
    ) {
    }

    /**
     * @param  null|\Closure(string): void  $debugLogger
     */
    public function runEntity(
        string $entity,
        Account $account,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $stockDate = null,
        bool $verbose = false,
        ?\Closure $debugLogger = null,
    ): int {
        $importer = $this->configureImporter($verbose, $debugLogger);
        $token = $this->accountResolver->getToken($account);
        $accountImporter = $importer->forAccount($account, $token);

        return match ($entity) {
            'incomes' => $this->importIncomes($accountImporter, $account, $dateFrom, $dateTo),
            'orders' => $this->importOrders($accountImporter, $account, $dateFrom, $dateTo),
            'sales' => $this->importSales($accountImporter, $account, $dateFrom, $dateTo),
            'stocks' => $this->importStocks($accountImporter, $account, $stockDate),
            default => throw new \InvalidArgumentException("Unknown entity: {$entity}"),
        };
    }

    /** @return array{date_from: string, date_to: ?string, fresh: bool} */
    public function resolveRange(string $entity, Account $account, ?string $dateFrom, ?string $dateTo): array
    {
        if ($entity === 'stocks') {
            return [
                'date_from' => $dateFrom ?? $this->rangeResolver->resolveStockDate(),
                'date_to' => null,
                'fresh' => $dateFrom === null,
            ];
        }

        if ($dateFrom !== null || $dateTo !== null) {
            return [
                'date_from' => $dateFrom ?? config('wb_api.date_from'),
                'date_to' => $dateTo ?? config('wb_api.date_to'),
                'fresh' => false,
            ];
        }

        return $this->rangeResolver->resolve($entity, $account->id, null, null);
    }

    /**
     * @param  null|\Closure(string): void  $debugLogger
     */
    private function configureImporter(bool $verbose, ?\Closure $debugLogger): WbDataImporter
    {
        $importer = $this->importer->setVerbose($verbose);

        if ($debugLogger !== null) {
            $importer->setDebugLogger($debugLogger);
        }

        return $importer;
    }

    private function importIncomes(WbDataImporter $importer, Account $account, ?string $dateFrom, ?string $dateTo): int
    {
        $range = $this->resolveRange('incomes', $account, $dateFrom, $dateTo);
        $count = $importer->import(
            endpoint: 'incomes',
            model: new Income,
            fillable: (new Income)->getFillable(),
            dateFrom: $range['date_from'],
            dateTo: $range['date_to'],
            uniqueBy: ['income_id', 'supplier_article', 'barcode', 'tech_size'],
        );
        $importer->markSynced('incomes', $range['date_from']);

        return $count;
    }

    private function importOrders(WbDataImporter $importer, Account $account, ?string $dateFrom, ?string $dateTo): int
    {
        $range = $this->resolveRange('orders', $account, $dateFrom, $dateTo);
        $count = $importer->importOrders($range['date_from'], $range['date_to']);
        $importer->markSynced('orders', $range['date_from']);

        return $count;
    }

    private function importSales(WbDataImporter $importer, Account $account, ?string $dateFrom, ?string $dateTo): int
    {
        $range = $this->resolveRange('sales', $account, $dateFrom, $dateTo);
        $count = $importer->import(
            endpoint: 'sales',
            model: new Sale,
            fillable: (new Sale)->getFillable(),
            dateFrom: $range['date_from'],
            dateTo: $range['date_to'],
            uniqueBy: ['sale_id'],
            rowTransformer: function (array $record): array {
                if (empty($record['sale_id'])) {
                    return [];
                }

                if (! array_key_exists('is_storno', $record) || $record['is_storno'] === null) {
                    $record['is_storno'] = false;
                }

                return $record;
            },
        );
        $importer->markSynced('sales', $range['date_from']);

        return $count;
    }

    private function importStocks(WbDataImporter $importer, Account $account, ?string $stockDate): int
    {
        $date = $stockDate !== null
            ? WbDataImporter::stockDate($stockDate)
            : $this->rangeResolver->resolveStockDate();

        $count = $importer->import(
            endpoint: 'stocks',
            model: new Stock,
            fillable: (new Stock)->getFillable(),
            dateFrom: $date,
            dateTo: null,
            uniqueBy: ['date', 'nm_id', 'warehouse_name', 'barcode', 'tech_size'],
            rowTransformer: function (array $record) use ($date) {
                $record['date'] = $date;

                foreach (['in_way_to_client', 'in_way_from_client', 'quantity'] as $field) {
                    if (! array_key_exists($field, $record) || $record[$field] === null) {
                        $record[$field] = 0;
                    }
                }

                return (! isset($record['nm_id']) || $record['nm_id'] === '' || empty($record['warehouse_name']))
                    ? []
                    : $record;
            },
        );
        $importer->markSynced('stocks', $date);

        return $count;
    }
}
