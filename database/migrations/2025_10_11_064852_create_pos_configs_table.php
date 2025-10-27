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
        Schema::create('pos_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->after('id');
            $table->string('name');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            // Reemplazar sequence_ids por journal_ids
            $table->foreignId('receipt_journal_id')->constrained('journals');
            $table->foreignId('invoice_journal_id')->constrained('journals');
            $table->foreignId('default_customer_id')->constrained('customers');
            $table->boolean('apply_tax')->default(true);
            $table->decimal('tax_rate', 5, 4)->default(0.18);
            $table->boolean('prices_include_tax')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_configs');
    }
};
