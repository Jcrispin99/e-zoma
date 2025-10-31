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
        Schema::create('qr_styles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);

            // Dimensiones de la etiqueta (en mm para impresión)
            $table->unsignedSmallInteger('label_width')->default(50);
            $table->unsignedSmallInteger('label_height')->default(50);

            // Diseño y estructura
            $table->string('layout_type')->default('default'); // Ej: 'default', 'qr_left', 'qr_right'
            $table->unsignedSmallInteger('qr_size')->default(150); // Tamaño del QR en px

            // Visibilidad del contenido
            $table->boolean('show_product_name')->default(true);
            $table->boolean('show_description')->default(true);
            $table->boolean('show_price')->default(true);
            $table->boolean('show_sku')->default(true);
            $table->boolean('show_barcode_text')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_styles');
    }
};
