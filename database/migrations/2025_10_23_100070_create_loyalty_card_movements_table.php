<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('loyalty_card_movements')) {
            return;
        }
        Schema::create('loyalty_card_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('loyalty_cards')->cascadeOnDelete();
            $table->enum('type', ['earn', 'spend', 'adjust']);
            $table->decimal('points', 16, 2);
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('pos_order_id')->nullable()->constrained('pos_orders')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->index(['card_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_card_movements');
    }
};
