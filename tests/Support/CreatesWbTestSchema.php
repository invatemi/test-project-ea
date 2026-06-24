<?php

namespace Tests\Support;

use App\Models\Account;
use App\Models\AccountToken;
use App\Models\ApiService;
use App\Models\Company;
use App\Models\TokenType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesWbTestSchema
{
    protected function createWbDataTables(): void
    {
        if (! Schema::hasTable('incomes')) {
            Schema::create('incomes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->unsignedBigInteger('income_id');
                $table->string('supplier_article', 128);
                $table->string('barcode')->nullable();
                $table->string('tech_size')->nullable();
                $table->dateTime('date');
                $table->string('warehouse_name');
                $table->unsignedInteger('quantity')->default(0);
                $table->decimal('total_price', 12, 2)->default(0);
                $table->timestamps();
                $table->unique(['account_id', 'income_id', 'supplier_article', 'barcode', 'tech_size'], 'uq_incomes');
            });
        }

        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->string('srid')->nullable();
                $table->string('odid')->nullable();
                $table->dateTime('date');
                $table->string('g_number')->nullable();
                $table->decimal('total_price', 12, 2)->nullable();
                $table->boolean('is_cancel')->default(false);
                $table->timestamps();
                $table->unique(['account_id', 'srid'], 'uq_orders_srid');
                $table->unique(['account_id', 'odid'], 'uq_orders_odid');
            });
        }

        if (! Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->string('sale_id');
                $table->dateTime('date');
                $table->timestamps();
                $table->unique(['account_id', 'sale_id'], 'uq_sales');
            });
        }

        if (! Schema::hasTable('stocks')) {
            Schema::create('stocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->date('date');
                $table->bigInteger('nm_id');
                $table->string('warehouse_name');
                $table->string('barcode')->nullable();
                $table->string('tech_size')->nullable();
                $table->unsignedInteger('quantity')->default(0);
                $table->timestamps();
                $table->unique(['account_id', 'date', 'nm_id', 'warehouse_name', 'barcode', 'tech_size'], 'uq_stocks');
            });
        }
    }

    /** @return array{company: Company, account: Account, service: ApiService, type: TokenType, token: AccountToken} */
    protected function seedAccountWithToken(int $accountSuffix = 1, string $apiKey = 'test-key'): array
    {
        $company = Company::query()->create(['name' => "Company {$accountSuffix}"]);
        $account = Account::query()->create([
            'company_id' => $company->id,
            'name' => "account-{$accountSuffix}",
            'is_active' => true,
        ]);

        $service = ApiService::query()->firstOrCreate(
            ['slug' => 'wb_test'],
            ['name' => 'WB Test', 'base_url' => 'http://test-api.local'],
        );
        if ($service->base_url !== 'http://test-api.local') {
            $service->update(['base_url' => 'http://test-api.local']);
        }

        $type = TokenType::query()->firstOrCreate(
            ['slug' => 'api_key'],
            ['name' => 'API Key'],
        );

        $service->tokenTypes()->syncWithoutDetaching([$type->id]);

        $token = AccountToken::query()->firstOrNew([
            'account_id' => $account->id,
            'api_service_id' => $service->id,
            'token_type_id' => $type->id,
        ]);
        $token->is_active = true;
        $token->setCredentialsArray(['key' => $apiKey]);
        $token->save();

        return compact('company', 'account', 'service', 'type', 'token');
    }
}
