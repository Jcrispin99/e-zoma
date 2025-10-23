<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('loyalty_rewards')) {
            return;
        }
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies');

            // Descuentos / productos regalo
            $table->string('reward_type'); // discount, product
            $table->string('discount_mode')->nullable(); // percent, per_order, per_point
            $table->string('discount_applicability')->nullable(); // order, specific
            $table->decimal('discount', 12, 2)->nullable(); // % o monto según modo
            $table->decimal('discount_max_amount', 12, 2)->nullable();

            $table->foreignId('reward_product_id')->nullable()->constrained('variants');
            $table->unsignedInteger('reward_product_qty')->nullable();

            // Requisitos y efectos wallet
            $table->decimal('required_points', 12, 2)->nullable();
            $table->boolean('clear_wallet')->default(false);

            $table->json('description')->nullable();
            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards');
    }
};
