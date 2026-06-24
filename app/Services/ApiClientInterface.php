<?php

namespace App\Services;

use App\Models\AccountToken;
use Illuminate\Http\Client\Response;

interface ApiClientInterface
{
    public function fetch(string $endpoint, string $dateFrom, ?string $dateTo = null, int $page = 1): Response;
}
