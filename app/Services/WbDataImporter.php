<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WbDataImporter
{
    public function __construct(private readonly WbApiClient $client)
    {
    }

    public function import(
        string $endpoint,
        Model $model,
        array $fillable,
        string $dateFrom,
        ?string $dateTo = null,
        ?array $uniqueBy = null,
        ?callable $rowTransformer = null,
    ): int {
        return $this->paginate($endpoint, $dateFrom, $dateTo, function (array $rows) use ($model, $fillable, $uniqueBy, $rowTransformer): int {
            $payload = $this->buildPayload($rows, $fillable, $rowTransformer);

            if ($payload === []) {
                return 0;
            }

            if ($uniqueBy !== null) {
                $updateColumns = array_values(array_diff(array_keys($payload[0]), $uniqueBy));
                $model->newQuery()->upsert($payload, $uniqueBy, $updateColumns);
            } else {
                $model->newQuery()->insert($payload);
            }

            return count($payload);
        });
    }

    public function importOrders(string $dateFrom, ?string $dateTo = null): int
    {
        $fillable = (new Order)->getFillable();

        return $this->paginate('orders', $dateFrom, $dateTo, function (array $rows) use ($fillable): int {
            $bySrid = [];
            $byOdid = [];
            $withoutKey = [];

            foreach ($rows as $row) {
                $record = $this->nullifyPlaceholderIds(
                    Arr::only($this->normalizeRow($row), $fillable)
                );

                if ($this->isEmptyRecord($record)) {
                    continue;
                }

                if ($this->hasUniqueKey($record['srid'] ?? null)) {
                    $bySrid[] = $record;
                } elseif ($this->hasUniqueKey($record['odid'] ?? null)) {
                    $byOdid[] = $record;
                } else {
                    $withoutKey[] = $record;
                }
            }

            $imported = $this->upsertBatch(new Order, $bySrid, ['srid']);
            $imported += $this->upsertBatch(new Order, $byOdid, ['odid']);

            if ($withoutKey !== []) {
                Order::query()->insert($withoutKey);
                $imported += count($withoutKey);
            }

            return $imported;
        });
    }

    public static function stockDate(?string $date = null): string
    {
        return Carbon::parse($date ?? now())->format('Y-m-d');
    }

    /**
     * @param  callable(array<int, array<string, mixed>>): int  $processPage
     */
    private function paginate(string $endpoint, string $dateFrom, ?string $dateTo, callable $processPage): int
    {
        DB::connection()->disableQueryLog();

        $page = 1;
        $imported = 0;
        $lastPage = null;

        while (true) {
            $response = $this->client->fetch($endpoint, $dateFrom, $dateTo, $page);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "API {$endpoint} failed on page {$page}: HTTP {$response->status()}"
                );
            }

            $lastPage ??= (int) ($response->json('meta.last_page') ?: 0);
            $rows = $response->json('data') ?? [];

            if ($rows === [] || ($lastPage > 0 && $page > $lastPage)) {
                break;
            }

            $imported += $processPage($rows);

            if ($lastPage > 0 && $page >= $lastPage) {
                break;
            }

            $page++;
        }

        return $imported;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildPayload(array $rows, array $fillable, ?callable $rowTransformer): array
    {
        $payload = [];

        foreach ($rows as $row) {
            $normalized = $this->normalizeRow($row);
            $record = Arr::only($normalized, $fillable);

            if ($rowTransformer !== null) {
                $record = $rowTransformer($record, $normalized);
            }

            if ($this->isEmptyRecord($record)) {
                continue;
            }

            $payload[] = $record;
        }

        return $payload;
    }

    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[Str::snake($key)] = $value;
        }

        return $normalized;
    }

    private function isEmptyRecord(array $record): bool
    {
        return collect($record)
            ->except(['created_at', 'updated_at'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->isEmpty();
    }

    private function hasUniqueKey(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== 0 && $value !== '0';
    }

    /** @param  array<string, mixed>  $record */
    private function nullifyPlaceholderIds(array $record): array
    {
        foreach (['odid', 'srid'] as $key) {
            if (($record[$key] ?? null) === 0 || ($record[$key] ?? null) === '0') {
                $record[$key] = null;
            }
        }

        return $record;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $uniqueBy
     */
    private function upsertBatch(Model $model, array $rows, array $uniqueBy): int
    {
        if ($rows === []) {
            return 0;
        }

        $updateColumns = array_values(array_diff(array_keys($rows[0]), $uniqueBy));
        $model->newQuery()->upsert($rows, $uniqueBy, $updateColumns);

        return count($rows);
    }
}
