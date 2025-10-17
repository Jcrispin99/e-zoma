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
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('type');
            // Código de tipo de comprobante SUNAT (ej. 01 = Factura, 03 = Boleta)
            $table->string('document_type_code', 2)->nullable();

            $table->foreignId('sequence_id')->constrained('sequences');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['document_type_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
