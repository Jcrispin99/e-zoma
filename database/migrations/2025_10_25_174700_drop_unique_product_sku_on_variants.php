<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            // Remove unique constraint to allow duplicate SKUs per product
            $table->dropUnique('variants_product_sku_unique');
            // Optional: keep a non-unique index to speed up lookups
            $table->index(['product_id', 'sku'], 'variants_product_sku_index');
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            // Restore previous unique constraint
            $table->dropIndex('variants_product_sku_index');
            $table->unique(['product_id', 'sku'], 'variants_product_sku_unique');
        });
    }
};