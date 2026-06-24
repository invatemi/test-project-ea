<?php

namespace App\Services;

use App\Models\AccountSyncState;
use Carbon\Carbon;

class FreshDataRangeResolver
{
    public function resolve(string $entity, int $accountId, ?string $manualFrom = null, ?string $manualTo = null): array
    {
        if ($manualFrom !== null) {
            return [
                'date_from' => $manualFrom,
                'date_to' => $manualTo ?? config('wb_api.date_to'),
                'fresh' => false,
            ];
        }

        $today = Carbon::today();
        $bufferDays = (int) config('wb_api.fresh_buffer_days', 1);

        $syncState = AccountSyncState::query()
            ->where('account_id', $accountId)
            ->where('entity', $entity)
            ->first();

        $dateFrom = $syncState?->last_date_from
            ? Carbon::parse($syncState->last_date_from)->subDays($bufferDays)
            : $today->copy()->subDays($bufferDays);

        if ($dateFrom->gt($today)) {
            $dateFrom = $today->copy()->subDays($bufferDays);
        }

        return [
            'date_from' => $dateFrom->format('Y-m-d'),
            'date_to' => $today->format('Y-m-d'),
            'fresh' => true,
        ];
    }

    public function resolveStockDate(?string $manualDate = null): string
    {
        return WbDataImporter::stockDate($manualDate ?? now()->format('Y-m-d'));
    }
}
