<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incomes')) {
            Schema::create('incomes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('income_id');
                $table->string('number', 64)->nullable();
                $table->dateTime('date');
                $table->dateTime('last_change_date')->nullable();
                $table->string('supplier_article');
                $table->string('tech_size', 64)->nullable();
                $table->string('barcode', 32)->nullable();
                $table->unsignedInteger('quantity')->default(0);
                $table->decimal('total_price', 12, 2)->default(0);
                $table->dateTime('date_close')->nullable();
                $table->string('warehouse_name');
                $table->bigInteger('nm_id')->nullable();
                $table->string('status', 64)->nullable();
                $table->timestamps();

                $table->unique(['account_id', 'income_id', 'supplier_article', 'barcode', 'tech_size'], 'uq_incomes_business');
                $table->index(['account_id', 'date'], 'idx_incomes_account_date');
                $table->index('last_change_date', 'idx_incomes_last_change_date');
                $table->index('nm_id', 'idx_incomes_nm_id');
            });
        }

        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained()->cascadeOnDelete();
                $table->string('g_number', 64)->nullable();
                $table->dateTime('date');
                $table->dateTime('last_change_date')->nullable();
                $table->string('supplier_article')->nullable();
                $table->string('tech_size', 64)->nullable();
                $table->string('barcode', 32)->nullable();
                $table->decimal('total_price', 12, 2)->nullable();
                $table->integer('discount_percent')->nullable();
                $table->string('warehouse_name')->nullable();
                $table->string('warehouse_type', 128)->nullable();
                $table->string('country_name', 128)->nullable();
                $table->string('oblast')->nullable();
                $table->string('oblast_okrug_name')->nullable();
                $table->string('region_name')->nullable();
                $table->unsignedBigInteger('income_id')->nullable();
                $table->string('odid', 64)->nullable();
                $table->string('srid', 128)->nullable();
                $table->bigInteger('nm_id')->nullable();
                $table->string('subject')->nullable();
                $table->string('category')->nullable();
                $table->string('brand')->nullable();
                $table->boolean('is_supply')->nullable();
                $table->boolean('is_realization')->nullable();
                $table->decimal('spp', 12, 2)->nullable();
                $table->decimal('finished_price', 12, 2)->nullable();
                $table->decimal('price_with_disc', 12, 2)->nullable();
                $table->boolean('is_cancel')->default(false);
                $table->dateTime('cancel_dt')->nullable();
                $table->string('sticker', 64)->nullable();
                $table->timestamps();

                $table->unique(['account_id', 'odid'], 'uq_orders_account_odid');
                $table->unique(['account_id', 'srid'], 'uq_orders_account_srid');
                $table->index(['account_id', 'date'], 'idx_orders_account_date');
                $table->index('last_change_date', 'idx_orders_last_change_date');
                $table->index('g_number', 'idx_orders_g_number');
                $table->index('nm_id', 'idx_orders_nm_id');
                $table->index('income_id', 'idx_orders_income_id');
            });
        }

        if (! Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained()->cascadeOnDelete();
                $table->string('g_number', 64)->nullable();
                $table->dateTime('date');
                $table->dateTime('last_change_date')->nullable();
                $table->string('supplier_article')->nullable();
                $table->string('tech_size', 64)->nullable();
                $table->string('barcode', 32)->nullable();
                $table->decimal('total_price', 12, 2)->nullable();
                $table->integer('discount_percent')->nullable();
                $table->boolean('is_supply')->nullable();
                $table->boolean('is_realization')->nullable();
                $table->decimal('promo_code_discount', 12, 2)->nullable();
                $table->string('warehouse_name')->nullable();
                $table->string('country_name', 128)->nullable();
                $table->string('oblast_okrug_name')->nullable();
                $table->string('region_name')->nullable();
                $table->unsignedBigInteger('income_id')->nullable();
                $table->string('sale_id', 32);
                $table->string('odid', 64)->nullable();
                $table->string('srid', 128)->nullable();
                $table->decimal('spp', 12, 2)->nullable();
                $table->decimal('for_pay', 12, 2)->nullable();
                $table->decimal('finished_price', 12, 2)->nullable();
                $table->decimal('price_with_disc', 12, 2)->nullable();
                $table->bigInteger('nm_id')->nullable();
                $table->string('subject')->nullable();
                $table->string('category')->nullable();
                $table->string('brand')->nullable();
                $table->boolean('is_storno')->default(false);
                $table->string('sticker', 64)->nullable();
                $table->timestamps();

                $table->unique(['account_id', 'sale_id'], 'uq_sales_account_sale_id');
                $table->index(['account_id', 'date'], 'idx_sales_account_date');
                $table->index('last_change_date', 'idx_sales_last_change_date');
                $table->index('g_number', 'idx_sales_g_number');
                $table->index('nm_id', 'idx_sales_nm_id');
                $table->index('odid', 'idx_sales_odid');
            });
        }

        if (! Schema::hasTable('stocks')) {
            Schema::create('stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained()->cascadeOnDelete();
                $table->date('date');
                $table->dateTime('last_change_date')->nullable();
                $table->string('supplier_article')->nullable();
                $table->string('tech_size', 64)->nullable();
                $table->string('barcode', 32)->nullable();
                $table->unsignedInteger('quantity')->default(0);
                $table->unsignedInteger('quantity_full')->nullable();
                $table->unsignedInteger('quantity_not_in_orders')->nullable();
                $table->boolean('is_supply')->nullable();
                $table->boolean('is_realization')->nullable();
                $table->unsignedInteger('warehouse')->nullable();
                $table->string('warehouse_name');
                $table->unsignedInteger('in_way_to_client')->default(0);
                $table->unsignedInteger('in_way_from_client')->default(0);
                $table->bigInteger('nm_id');
                $table->string('subject')->nullable();
                $table->string('category')->nullable();
                $table->string('brand')->nullable();
                $table->unsignedInteger('days_on_site')->nullable();
                $table->string('sc_code', 64)->nullable();
                $table->decimal('price', 12, 2)->nullable();
                $table->decimal('discount', 12, 2)->nullable();
                $table->timestamps();

                $table->unique(['account_id', 'date', 'nm_id', 'warehouse_name', 'barcode', 'tech_size'], 'uq_stocks_snapshot');
                $table->index(['account_id', 'date'], 'idx_stocks_account_date');
                $table->index('last_change_date', 'idx_stocks_last_change_date');
                $table->index('supplier_article', 'idx_stocks_supplier_article');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('incomes');
    }
};
