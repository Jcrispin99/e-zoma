<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('loyalty_rules')) {
            return;
        }
        Schema::create('loyalty_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies');

            // Filtros
            $table->foreignId('product_category_id')->nullable()->constrained('categories');
            $table->unsignedBigInteger('product_tag_id')->nullable(); // reservado si se implementa tags
            $table->string('product_domain')->nullable(); // expresión/filtro avanzada (JSON/simple string)

            // Condiciones mínimas
            $table->unsignedInteger('minimum_qty')->default(0);
            $table->decimal('minimum_amount', 12, 2)->nullable();
            $table->string('minimum_amount_tax_mode')->nullable(); // with_tax / without_tax

            // Disparo
            $table->string('mode')->default('auto'); // auto, with_code
            $table->string('code')->nullable(); // código promocional
            $table->string('promo_barcode')->nullable(); // para POS
            $table->unsignedBigInteger('website_id')->nullable();

            // Puntos (para programas loyalty)
            $table->string('reward_point_mode')->nullable(); // money, order

            $table->boolean('active')->default(true);
            $table->boolean('reward_point_split')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_rules');
    }
};
