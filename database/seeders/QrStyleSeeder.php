<?php

namespace Database\Seeders;

use App\Models\QrStyle;
use Illuminate\Database\Seeder;

class QrStyleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Estilo 1: Etiqueta Cuadrada Estándar (por defecto)
        QrStyle::create([
            'name' => 'Etiqueta Cuadrada Estándar',
            'is_default' => true,
            'label_width' => 70, // mm
            'label_height' => 70, // mm
            'layout_type' => 'default', // QR centrado, texto abajo
            'qr_size' => 150, // px
            'show_product_name' => true,
            'show_description' => true,
            'show_price' => true,
            'show_sku' => true,
            'show_barcode_text' => true,
        ]);

        // Estilo 2: Etiqueta Rectangular (QR Izquierda)
        QrStyle::create([
            'name' => 'Etiqueta Rectangular (QR Izquierda)',
            'is_default' => false,
            'label_width' => 80, // mm
            'label_height' => 40, // mm
            'layout_type' => 'qr_left', // QR a la izquierda, texto a la derecha
            'qr_size' => 100, // px (más pequeño para caber en un lado)
            'show_product_name' => true,
            'show_description' => true,
            'show_price' => true,
            'show_sku' => true,
            'show_barcode_text' => true,
        ]);
    }
}
