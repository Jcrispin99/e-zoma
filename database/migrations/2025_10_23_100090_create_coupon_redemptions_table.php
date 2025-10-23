<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('coupon_redemptions')) {
            return;
        }
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('loyalty_cards')->cascadeOnDelete();
            $table->string('code')->index();
            $table->enum('channel', ['sale', 'ecommerce', 'pos']);
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('pos_order_id')->nullable()->constrained('pos_orders')->nullOnDelete();
            $table->timestamp('used_at');
            $table->timestamps();
            $table->index(['card_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
    }
};
