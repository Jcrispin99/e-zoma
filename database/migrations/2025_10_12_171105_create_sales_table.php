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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->integer('voucher_type');

            $table->string('serie');
            $table->string('correlative');

            // Diario contable (define tipo de comprobante: 01/03/07)
            $table->foreignId('journal_id')->constrained('journals')->onDelete('cascade');

            $table->timestamp('date')
                ->useCurrent();

            $table->foreignId('quote_id')->nullable()->constrained()->onDelete('set null');

            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('pos_order_id')
                ->nullable()
                ->constrained('pos_orders')
                ->after('warehouse_id');

            $table->decimal('total', 10, 2)->default(0.00);

            $table->string('observation')->nullable();

            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Estados de ciclo de venta y pago (alineado con Odoo)
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');

            // Vínculo a documento original para Notas de Crédito (SUNAT 07)
            $table->foreignId('original_sale_id')->nullable()->constrained('sales')->onDelete('set null');
            $table->foreignId('reason_id')->nullable()->constrained('reasons')->onDelete('set null');
            $table->string('original_document_type_code', 2)->nullable(); // 01/03 del doc original
            $table->string('original_serie')->nullable();
            $table->string('original_correlative')->nullable();

            $table->timestamps();

            // Índices y unicidad
            $table->index(['status']);
            $table->index(['payment_status']);
            $table->index(['journal_id']);
            $table->index(['original_sale_id']);
            $table->unique(['company_id', 'voucher_type', 'serie', 'correlative'], 'sale_unique_company_voucher_serie_corr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
