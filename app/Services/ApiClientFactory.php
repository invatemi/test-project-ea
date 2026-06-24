<?php

namespace App\Services;

use App\Models\AccountToken;
use App\Models\ApiService;

class ApiClientFactory
{
    public function make(AccountToken $token): ApiClientInterface
    {
        $token->loadMissing(['apiService', 'tokenType']);

        $slug = $token->apiService?->slug;

        return match ($slug) {
            'wb_test', null => app(WbApiClient::class)->forToken($token),
            default => app(WbApiClient::class)->forToken($token),
        };
    }

    public function makeForService(ApiService $service, AccountToken $token): ApiClientInterface
    {
        return $this->make($token);
    }
}
