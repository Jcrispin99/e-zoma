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
        if (Schema::hasTable('loyalty_programs')) {
            return;
        }
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sequence')->default(10);
            $table->foreignId('company_id')->nullable()->constrained('companies');

            $table->string('program_type'); // loyalty, promotion, promo_code, coupons, buy_x_get_y, gift_card, next_order_coupons
            $table->string('applies_on')->default('current'); // current, future, both
            $table->string('trigger')->nullable(); // auto, with_code

            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();

            $table->boolean('active')->default(true);

            // Canales
            $table->boolean('sale_ok')->default(true);
            $table->boolean('ecommerce_ok')->default(false);
            $table->boolean('pos_ok')->default(false);

            // Límites
            $table->boolean('limit_usage')->nullable();
            $table->unsignedInteger('max_usage')->nullable();

            $table->unsignedBigInteger('website_id')->nullable(); // reservado para integración web futura

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_programs');
    }
};
