<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Reason;

class ReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reasons = [
            // Razones para Ingreso (type = 1)
            [
                'name' => 'Compra a proveedor',
                'type' => 1,
            ],
            [
                'name' => 'Ajuste de Inventario (Ingreso)',
                'type' => 1,
            ],
            [
                'name' => 'Devolución de cliente',
                'type' => 1,
            ],
            [
                'name' => 'Producción terminada',
                'type' => 1,
            ],
            [
                'name' => 'Transferencia desde otro almacén',
                'type' => 1,
            ],
            [
                'name' => 'Error en salida (Corrección)',
                'type' => 1,
            ],

            // Razones para Salida (type = 2)
            [
                'name' => 'Venta',
                'type' => 2,
            ],
            [
                'name' => 'Ajuste de Inventario (Salida)',
                'type' => 2,
            ],
            [
                'name' => 'Devolución a proveedor',
                'type' => 2,
            ],
            [
                'name' => 'Mercancía dañada o vencida',
                'type' => 2,
            ],
            [
                'name' => 'Robo o pérdida',
                'type' => 2,
            ],
            [
                'name' => 'Transferencia a otro almacén',
                'type' => 2,
            ],
            [
                'name' => 'Uso interno o consumo',
                'type' => 2,
            ],
        ];

        foreach ($reasons as $reason) {
            Reason::create($reason);
        }
    }
}
