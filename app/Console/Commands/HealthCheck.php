<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HealthCheck extends Command
{
    protected $signature = 'app:health';

    protected $description = 'Проверка доступности приложения и БД';

    public function handle(): int
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->error('Database unavailable: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
