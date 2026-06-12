<?php

namespace App\Console\Commands\Concerns;

trait ResolvesImportDateRange
{
    protected function resolveDateFrom(): string
    {
        return $this->option('date-from') ?? config('wb_api.date_from');
    }

    protected function resolveDateTo(): string
    {
        return $this->option('date-to') ?? config('wb_api.date_to');
    }
}
