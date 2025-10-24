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
        Schema::table('loyalty_rewards', function (Blueprint $table) {
            $table->boolean('consume_all_points')->default(false)->after('points_cost');
            $table->foreignId('discount_category_id')->nullable()->after('discount_scope')->constrained('categories')->onDelete('cascade');
        });

        Schema::create('loyalty_reward_variant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_id')->constrained('loyalty_rewards')->onDelete('cascade');
            $table->foreignId('variant_id')->constrained('variants')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['reward_id', 'variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_rewards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_category_id');
            $table->dropColumn('consume_all_points');
        });

        Schema::dropIfExists('loyalty_reward_variant');
    }
};