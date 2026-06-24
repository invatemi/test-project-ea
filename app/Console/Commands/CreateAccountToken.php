<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountToken;
use App\Models\ApiService;
use App\Models\TokenType;
use Illuminate\Console\Command;

class CreateAccountToken extends Command
{
    protected $signature = 'app:account-token:create
                            {account_id : ID аккаунта}
                            {service_slug : Slug API-сервиса}
                            {type_slug : Slug типа токена}
                            {--key= : Значение API key}
                            {--token= : Bearer token}
                            {--login= : Login}
                            {--password= : Password}';

    protected $description = 'Создать или обновить токен аккаунта для API-сервиса';

    public function handle(): int
    {
        $account = Account::query()->find($this->argument('account_id'));
        $service = ApiService::findBySlug($this->argument('service_slug'));
        $type = TokenType::findBySlug($this->argument('type_slug'));

        if ($account === null || $service === null || $type === null) {
            $this->error('Аккаунт, API-сервис или тип токена не найден.');

            return self::FAILURE;
        }

        if (! $service->tokenTypes()->where('token_types.id', $type->id)->exists()) {
            $this->error("Тип «{$type->slug}» не разрешён для сервиса «{$service->slug}». Используйте app:api-service:attach-token-type.");

            return self::FAILURE;
        }

        $credentials = $this->buildCredentials($type->slug);

        if ($credentials === []) {
            $this->error('Не указаны credentials (--key, --token или --login/--password).');

            return self::FAILURE;
        }

        $token = AccountToken::query()->updateOrCreate(
            [
                'account_id' => $account->id,
                'api_service_id' => $service->id,
                'token_type_id' => $type->id,
            ],
            ['is_active' => true],
        );

        $token->setCredentialsArray($credentials);
        $token->save();

        $this->info("Токен сохранён: account={$account->name}, service={$service->slug}, type={$type->slug}");

        return self::SUCCESS;
    }

    /** @return array<string, string> */
    private function buildCredentials(string $slug): array
    {
        return match ($slug) {
            'api_key' => $this->option('key') ? ['key' => (string) $this->option('key')] : [],
            'bearer' => $this->option('token') ? ['token' => (string) $this->option('token')] : [],
            'login_password' => ($this->option('login') && $this->option('password'))
                ? ['login' => (string) $this->option('login'), 'password' => (string) $this->option('password')]
                : [],
            default => $this->option('key') ? ['key' => (string) $this->option('key')] : [],
        };
    }
}
