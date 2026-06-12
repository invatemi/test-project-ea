<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE incomes MODIFY nm_id BIGINT NULL');
        DB::statement('ALTER TABLE orders MODIFY nm_id BIGINT NULL');
        DB::statement('ALTER TABLE sales MODIFY nm_id BIGINT NULL');
        DB::statement('ALTER TABLE stocks MODIFY nm_id BIGINT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE incomes MODIFY nm_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE orders MODIFY nm_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE sales MODIFY nm_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE stocks MODIFY nm_id BIGINT UNSIGNED NOT NULL');
    }
};
