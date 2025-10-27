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
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('loyalty_programs')->onDelete('cascade');
            $table->string('name');
            $table->string('reward_type'); // 'free_product' | 'free_shipping' | 'discount'
            $table->unsignedInteger('points_cost')->default(0);
            $table->boolean('consume_all_points')->default(false);
            $table->string('discount_method')->nullable(); // 'percent' | 'soles_per_point' | 'soles_fixed'
            $table->string('discount_scope')->nullable(); // 'order' | 'cheapest_product' | 'specific_product'
            $table->foreignId('discount_category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('reward_product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('soles_per_point', 12, 4)->nullable();
            $table->decimal('fixed_amount', 12, 2)->nullable();
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards');
    }
};
