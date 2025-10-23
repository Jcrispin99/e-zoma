<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('loyalty_cards')) {
            return;
        }
        Schema::create('loyalty_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->foreignId('customer_id')->nullable()->constrained('customers');

            $table->string('code')->unique();
            $table->date('expiration_date')->nullable();

            $table->decimal('points', 12, 2)->default(0);

            $table->foreignId('sale_id')->nullable()->constrained('sales'); // orden vinculada (redeem)
            $table->foreignId('source_pos_order_id')->nullable()->constrained('pos_orders'); // orden POS origen (gift/next order)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_cards');
    }
};
