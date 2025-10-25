<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            // Drop global unique on sku and scope uniqueness per product
            $table->dropUnique('variants_sku_unique');
            $table->unique(['product_id', 'sku'], 'variants_product_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->dropUnique('variants_product_sku_unique');
            $table->unique('sku', 'variants_sku_unique');
        });
    }
};
