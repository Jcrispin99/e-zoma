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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->integer('voucher_type');

            $table->string('serie');
            $table->string('correlative');

            $table->timestamp('date')
                ->useCurrent();

            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');

            $table->decimal('total', 10, 2)->default(0.00);

            $table->string('observation')->nullable();

            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Ciclo global del PO
            $table->enum('status', ['draft', 'confirmed', 'done', 'cancelled'])->default('draft');
            $table->enum('receiving_status', ['none', 'partial', 'complete'])->default('none');
            $table->enum('billing_status', ['none', 'partial', 'complete'])->default('none');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('billed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->unsignedInteger('purchases_count')->default(0);
            $table->decimal('ordered_qty_total', 12, 4)->default(0);
            $table->decimal('received_qty_total', 12, 4)->default(0);
            $table->decimal('billed_qty_total', 12, 4)->default(0);

            $table->timestamps();

            // Índices para búsqueda y unicidad
            $table->index(['supplier_id']);
            $table->index(['company_id']);
            $table->index(['status']);
            $table->index(['receiving_status']);
            $table->index(['billing_status']);
            $table->index(['payment_status']);
            $table->unique(['company_id', 'voucher_type', 'serie', 'correlative'], 'po_unique_company_voucher_serie_corr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
