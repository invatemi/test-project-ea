<?php

namespace App\Console\Commands;

use App\Models\ApiService;
use Illuminate\Console\Command;

class CreateApiService extends Command
{
    protected $signature = 'app:api-service:create
                            {slug : Уникальный slug сервиса}
                            {base_url : Базовый URL API}
                            {name? : Отображаемое имя}';

    protected $description = 'Создать API-сервис';

    public function handle(): int
    {
        $service = ApiService::query()->create([
            'slug' => $this->argument('slug'),
            'base_url' => $this->argument('base_url'),
            'name' => $this->argument('name') ?? $this->argument('slug'),
        ]);

        $this->info("API-сервис создан: id={$service->id}, slug={$service->slug}");

        return self::SUCCESS;
    }
}
