<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loyalty_earn_rules', function (Blueprint $table) {
            $table->unsignedInteger('points_per_order')->nullable()->after('points_per_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_earn_rules', function (Blueprint $table) {
            $table->dropColumn('points_per_order');
        });
    }
};