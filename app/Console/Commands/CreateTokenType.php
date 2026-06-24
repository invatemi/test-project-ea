<?php

namespace App\Console\Commands;

use App\Models\TokenType;
use Illuminate\Console\Command;

class CreateTokenType extends Command
{
    protected $signature = 'app:token-type:create
                            {slug : Уникальный slug типа (api_key, bearer, login_password)}
                            {name? : Отображаемое имя}';

    protected $description = 'Создать тип токена';

    public function handle(): int
    {
        $type = TokenType::query()->create([
            'slug' => $this->argument('slug'),
            'name' => $this->argument('name') ?? $this->argument('slug'),
        ]);

        $this->info("Тип токена создан: id={$type->id}, slug={$type->slug}");

        return self::SUCCESS;
    }
}
