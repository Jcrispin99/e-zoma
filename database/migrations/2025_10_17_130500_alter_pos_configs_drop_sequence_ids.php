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
        Schema::table('pos_configs', function (Blueprint $table) {
            // Eliminar foreign keys y columnas de secuencia
            $table->dropConstrainedForeignId('receipt_sequence_id');
            $table->dropConstrainedForeignId('invoice_sequence_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_configs', function (Blueprint $table) {
            // Restaurar columnas y foreign keys de secuencia
            $table->foreignId('receipt_sequence_id')->constrained('sequences');
            $table->foreignId('invoice_sequence_id')->constrained('sequences');
        });
    }
};