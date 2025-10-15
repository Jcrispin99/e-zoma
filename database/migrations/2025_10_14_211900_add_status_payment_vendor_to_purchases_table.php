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
        Schema::table('purchases', function (Blueprint $table) {
            // Estados de ciclo de compra y pago
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');

            // Datos de factura del proveedor (opcional)
            $table->string('vendor_bill_number')->nullable();
            $table->date('vendor_bill_date')->nullable();

            // Índices y unicidad útil
            $table->index(['status']);
            $table->index(['payment_status']);
            $table->unique(['company_id', 'voucher_type', 'serie', 'correlative'], 'purchase_unique_company_voucher_serie_corr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Quitar unicidad e índices
            $table->dropUnique('purchase_unique_company_voucher_serie_corr');
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_status']);

            // Quitar columnas
            $table->dropColumn(['status', 'payment_status', 'vendor_bill_number', 'vendor_bill_date']);
        });
    }
};