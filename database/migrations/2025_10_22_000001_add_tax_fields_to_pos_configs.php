<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pos_configs', function (Blueprint $table) {
            $table->boolean('apply_tax')->default(true);
            $table->decimal('tax_rate', 5, 4)->default(0.18);
            $table->boolean('prices_include_tax')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_configs', function (Blueprint $table) {
            $table->dropColumn(['apply_tax', 'tax_rate', 'prices_include_tax']);
        });
    }
};