<?php

namespace Database\Seeders;

use App\Models\ApiService;
use App\Models\Company;
use App\Models\Account;
use App\Models\TokenType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WbApiSeeder extends Seeder
{
    public function run(): void
    {
        $baseUrl = config('wb_api.host');

        if (! $baseUrl) {
            $this->command?->warn('WB_API_HOST не задан — пропуск seed API-сервиса.');

            return;
        }

        ApiService::query()->updateOrCreate(
            ['slug' => config('wb_api.default_service', 'wb_test')],
            ['name' => 'WB Test API', 'base_url' => $baseUrl],
        );

        foreach ([
            ['slug' => 'api_key', 'name' => 'API Key (query parameter)'],
            ['slug' => 'bearer', 'name' => 'Bearer Token'],
            ['slug' => 'login_password', 'name' => 'Login and Password'],
        ] as $type) {
            TokenType::query()->firstOrCreate(['slug' => $type['slug']], ['name' => $type['name']]);
        }

        $service = ApiService::findBySlug(config('wb_api.default_service', 'wb_test'));
        $apiKeyType = TokenType::findBySlug('api_key');

        if ($service && $apiKeyType) {
            $service->tokenTypes()->syncWithoutDetaching([$apiKeyType->id]);
        }

        Company::query()->firstOrCreate(['name' => 'Legacy'], ['name' => 'Legacy']);

        $company = Company::query()->where('name', 'Legacy')->first();

        if ($company) {
            Account::query()->firstOrCreate(
                ['company_id' => $company->id, 'name' => 'default'],
                ['is_active' => true],
            );
        }

        if (config('wb_api.key') && $company) {
            $account = Account::query()->where('company_id', $company->id)->where('name', 'default')->first();
            $service = ApiService::findBySlug(config('wb_api.default_service', 'wb_test'));
            $type = TokenType::findBySlug('api_key');

            if ($account && $service && $type) {
                $token = \App\Models\AccountToken::query()->firstOrNew([
                    'account_id' => $account->id,
                    'api_service_id' => $service->id,
                    'token_type_id' => $type->id,
                ]);
                $token->is_active = true;
                $token->setCredentialsArray(['key' => config('wb_api.key')]);
                $token->save();
            }
        }
    }
}
