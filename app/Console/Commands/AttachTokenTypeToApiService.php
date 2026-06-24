<?php

namespace App\Console\Commands;

use App\Models\ApiService;
use App\Models\TokenType;
use Illuminate\Console\Command;

class AttachTokenTypeToApiService extends Command
{
    protected $signature = 'app:api-service:attach-token-type
                            {service_slug : Slug API-сервиса}
                            {type_slug : Slug типа токена}';

    protected $description = 'Привязать тип токена к API-сервису';

    public function handle(): int
    {
        $service = ApiService::findBySlug($this->argument('service_slug'));
        $type = TokenType::findBySlug($this->argument('type_slug'));

        if ($service === null || $type === null) {
            $this->error('API-сервис или тип токена не найден.');

            return self::FAILURE;
        }

        $service->tokenTypes()->syncWithoutDetaching([$type->id]);

        $this->info("Тип «{$type->slug}» привязан к сервису «{$service->slug}».");

        return self::SUCCESS;
    }
}
