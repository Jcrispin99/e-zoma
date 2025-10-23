<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('program_usages')) {
            return;
        }
        Schema::create('program_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->foreignId('reward_id')->nullable()->constrained('loyalty_rewards')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->enum('order_type', ['sale', 'pos']);
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('pos_order_id')->nullable()->constrained('pos_orders')->nullOnDelete();
            $table->string('code')->nullable();
            $table->decimal('discount_amount', 16, 2)->default(0);
            $table->decimal('points_used', 16, 2)->default(0);
            $table->enum('channel', ['sale', 'ecommerce', 'pos'])->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index(['program_id', 'order_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_usages');
    }
};
