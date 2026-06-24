<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('api_services')) {
            Schema::create('api_services', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 64)->unique();
                $table->string('name');
                $table->string('base_url', 512);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('token_types')) {
            Schema::create('token_types', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 64)->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('api_service_token_types')) {
            Schema::create('api_service_token_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('api_service_id')->constrained()->cascadeOnDelete();
                $table->foreignId('token_type_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['api_service_id', 'token_type_id']);
            });
        }

        if (! Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'name']);
            });
        }

        if (! Schema::hasTable('account_tokens')) {
            Schema::create('account_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained()->cascadeOnDelete();
                $table->foreignId('api_service_id')->constrained()->cascadeOnDelete();
                $table->foreignId('token_type_id')->constrained()->cascadeOnDelete();
                $table->text('credentials');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['account_id', 'api_service_id', 'token_type_id'], 'uq_account_token');
            });
        }

        if (! Schema::hasTable('account_sync_states')) {
            Schema::create('account_sync_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained()->cascadeOnDelete();
                $table->string('entity', 32);
                $table->timestamp('last_synced_at')->nullable();
                $table->date('last_date_from')->nullable();
                $table->timestamps();
                $table->unique(['account_id', 'entity']);
            });
        }

        if (DB::table('api_services')->where('slug', 'wb_test')->doesntExist()) {
            DB::table('api_services')->insert([
                'slug' => config('wb_api.default_service', 'wb_test'),
                'name' => 'WB Test API',
                'base_url' => config('wb_api.host', ''),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('token_types')->count() === 0) {
            DB::table('token_types')->insert([
                ['slug' => 'api_key', 'name' => 'API Key (query parameter)', 'created_at' => now(), 'updated_at' => now()],
                ['slug' => 'bearer', 'name' => 'Bearer Token', 'created_at' => now(), 'updated_at' => now()],
                ['slug' => 'login_password', 'name' => 'Login and Password', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        $serviceId = DB::table('api_services')->where('slug', 'wb_test')->value('id');
        $typeId = DB::table('token_types')->where('slug', 'api_key')->value('id');

        if ($serviceId && $typeId && DB::table('api_service_token_types')->where('api_service_id', $serviceId)->where('token_type_id', $typeId)->doesntExist()) {
            DB::table('api_service_token_types')->insert([
                'api_service_id' => $serviceId,
                'token_type_id' => $typeId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('companies')->where('id', 1)->doesntExist()) {
            DB::table('companies')->insert([
                'id' => 1,
                'name' => 'Legacy',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('accounts')->where('id', 1)->doesntExist()) {
            DB::table('accounts')->insert([
                'id' => 1,
                'company_id' => 1,
                'name' => 'default',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (['incomes', 'orders', 'sales', 'stocks'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! Schema::hasColumn($table, 'account_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('account_id')->default(1)->after('id');
                });
            }

            DB::table($table)->whereNull('account_id')->update(['account_id' => 1]);
        }

        $this->rebuildUniqueIndexes();
    }

    public function down(): void
    {
        foreach (['incomes', 'orders', 'sales', 'stocks'] as $table) {
            if (Schema::hasColumn($table, 'account_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('account_id');
                });
            }
        }

        Schema::dropIfExists('account_sync_states');
        Schema::dropIfExists('account_tokens');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('api_service_token_types');
        Schema::dropIfExists('token_types');
        Schema::dropIfExists('api_services');
        Schema::dropIfExists('companies');
    }

    private function rebuildUniqueIndexes(): void
    {
        if (Schema::hasTable('incomes')) {
            $this->dropIndexIfExists('incomes', 'uq_incomes_business');
            DB::statement('ALTER TABLE incomes ADD UNIQUE KEY uq_incomes_business (account_id, income_id, supplier_article, barcode, tech_size)');
        }

        if (Schema::hasTable('orders')) {
            $this->dropIndexIfExists('orders', 'uq_orders_odid');
            $this->dropIndexIfExists('orders', 'uq_orders_srid');
            $this->dropIndexIfExists('orders', 'uq_orders_account_odid');
            $this->dropIndexIfExists('orders', 'uq_orders_account_srid');
            DB::statement('ALTER TABLE orders ADD UNIQUE KEY uq_orders_account_odid (account_id, odid)');
            DB::statement('ALTER TABLE orders ADD UNIQUE KEY uq_orders_account_srid (account_id, srid)');
        }

        if (Schema::hasTable('sales')) {
            $this->dropIndexIfExists('sales', 'uq_sales_sale_id');
            $this->dropIndexIfExists('sales', 'uq_sales_account_sale_id');
            DB::statement('ALTER TABLE sales ADD UNIQUE KEY uq_sales_account_sale_id (account_id, sale_id)');
        }

        if (Schema::hasTable('stocks')) {
            $this->dropIndexIfExists('stocks', 'uq_stocks_snapshot');
            DB::statement('ALTER TABLE stocks ADD UNIQUE KEY uq_stocks_snapshot (account_id, date, nm_id, warehouse_name, barcode, tech_size)');
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        $exists = DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $index]
        );

        if ($exists !== []) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }
};
