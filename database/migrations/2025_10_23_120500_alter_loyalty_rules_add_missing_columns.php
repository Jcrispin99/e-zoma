<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('loyalty_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('loyalty_rules', 'product_category_id')) {
                $table->foreignId('product_category_id')->nullable()->constrained('categories');
            }
            if (!Schema::hasColumn('loyalty_rules', 'product_tag_id')) {
                $table->unsignedBigInteger('product_tag_id')->nullable();
            }
            if (!Schema::hasColumn('loyalty_rules', 'product_domain')) {
                $table->string('product_domain')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_rules', function (Blueprint $table) {
            if (Schema::hasColumn('loyalty_rules', 'product_category_id')) {
                $table->dropConstrainedForeignId('product_category_id');
            }
            if (Schema::hasColumn('loyalty_rules', 'product_tag_id')) {
                $table->dropColumn('product_tag_id');
            }
            if (Schema::hasColumn('loyalty_rules', 'product_domain')) {
                $table->dropColumn('product_domain');
            }
        });
    }
};
