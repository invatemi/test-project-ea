<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountToken;
use App\Models\ApiService;
use Illuminate\Support\Collection;

class AccountResolver
{
    public function resolve(?string $accountOption, bool $allAccounts, string $serviceSlug = 'wb_test'): Collection
    {
        if ($allAccounts) {
            return $this->activeAccountsWithToken($serviceSlug);
        }

        if ($accountOption !== null) {
            $account = is_numeric($accountOption)
                ? Account::query()->find($accountOption)
                : Account::query()->where('name', $accountOption)->first();

            if ($account === null) {
                throw new \InvalidArgumentException("Аккаунт «{$accountOption}» не найден.");
            }

            if (! $this->hasActiveToken($account, $serviceSlug)) {
                throw new \InvalidArgumentException("У аккаунта «{$account->name}» нет активного токена для {$serviceSlug}.");
            }

            return collect([$account]);
        }

        $accounts = $this->activeAccountsWithToken($serviceSlug);

        if ($accounts->isEmpty()) {
            $legacy = Account::query()->find(1);

            if ($legacy !== null) {
                return collect([$legacy]);
            }

            throw new \InvalidArgumentException('Нет активных аккаунтов с токеном. Создайте аккаунт и токен или укажите --account=.');
        }

        if ($accounts->count() === 1) {
            return $accounts;
        }

        throw new \InvalidArgumentException('Найдено несколько аккаунтов. Укажите --account= или --all-accounts.');
    }

    public function getToken(Account $account, string $serviceSlug = 'wb_test'): AccountToken
    {
        $service = ApiService::findBySlug($serviceSlug);

        if ($service === null) {
            throw new \InvalidArgumentException("API-сервис «{$serviceSlug}» не найден.");
        }

        $token = AccountToken::query()
            ->where('account_id', $account->id)
            ->where('api_service_id', $service->id)
            ->where('is_active', true)
            ->with(['apiService', 'tokenType'])
            ->first();

        if ($token !== null) {
            return $token;
        }

        $envKey = config('wb_api.key');

        if ($envKey && $serviceSlug === 'wb_test') {
            return $this->legacyTokenFromEnv($account, $service);
        }

        throw new \InvalidArgumentException("У аккаунта «{$account->name}» нет активного токена для {$serviceSlug}.");
    }

    /** @return Collection<int, Account> */
    private function activeAccountsWithToken(string $serviceSlug): Collection
    {
        $service = ApiService::findBySlug($serviceSlug);

        if ($service === null) {
            return collect();
        }

        return Account::query()
            ->where('is_active', true)
            ->whereHas('tokens', fn ($q) => $q
                ->where('api_service_id', $service->id)
                ->where('is_active', true))
            ->orderBy('id')
            ->get();
    }

    private function hasActiveToken(Account $account, string $serviceSlug): bool
    {
        try {
            $this->getToken($account, $serviceSlug);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    private function legacyTokenFromEnv(Account $account, ApiService $service): AccountToken
    {
        static $warned = false;

        if (! $warned) {
            fwrite(STDERR, "[WARN] Используется WB_API_KEY из .env. Создайте токен: app:account-token:create\n");
            $warned = true;
        }

        $type = $service->tokenTypes()->where('slug', 'api_key')->first()
            ?? $service->tokenTypes()->first();

        $token = new AccountToken([
            'account_id' => $account->id,
            'api_service_id' => $service->id,
            'token_type_id' => $type?->id ?? 1,
            'is_active' => true,
        ]);

        $token->setRelation('apiService', $service);
        $token->setRelation('tokenType', $type);
        $token->setCredentialsArray(['key' => config('wb_api.key')]);

        return $token;
    }
}
