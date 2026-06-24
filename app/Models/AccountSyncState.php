<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountSyncState extends Model
{
    public const ENTITIES = ['incomes', 'orders', 'sales', 'stocks'];

    protected $fillable = [
        'account_id',
        'entity',
        'last_synced_at',
        'last_date_from',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'last_date_from' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public static function markSynced(int $accountId, string $entity, string $dateFrom): void
    {
        static::query()->updateOrCreate(
            ['account_id' => $accountId, 'entity' => $entity],
            ['last_synced_at' => now(), 'last_date_from' => $dateFrom],
        );
    }
}
