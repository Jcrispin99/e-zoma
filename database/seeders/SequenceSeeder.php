<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Journal;
use App\Models\Sequence;
use Illuminate\Database\Seeder;

class SequenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1️⃣ Define los diarios (igual que antes)
        $journals = [
            ['name' => 'Cotizaciones',              'type' => 'quote',          'code' => 'COT',  'document_type_code' => null],
            ['name' => 'NOTA DE VENTA',              'type' => 'sale',           'code' => 'NV', 'document_type_code' => null],
            ['name' => 'FACTURA DE VENTA ELECTRONICA',              'type' => 'sale',           'code' => 'F001', 'document_type_code' => '01'],
            ['name' => 'BOLETA DE VENTA ELECTRONICA',               'type' => 'sale',           'code' => 'B001', 'document_type_code' => '03'],
            ['name' => 'Nota de Crédito Factura',   'type' => 'sale',           'code' => 'NCF001', 'document_type_code' => '07'],
            ['name' => 'Nota de Crédito Boleta',    'type' => 'sale',           'code' => 'NCB001', 'document_type_code' => '07'],
            ['name' => 'Nota de Débito Factura',    'type' => 'sale',           'code' => 'NDF001', 'document_type_code' => '08'],
            ['name' => 'Nota de Débito Boleta',     'type' => 'sale',           'code' => 'NDB001', 'document_type_code' => '08'],
            ['name' => 'Órdenes de Compra',         'type' => 'purchase-order', 'code' => 'OC',   'document_type_code' => null],
            ['name' => 'Compras',                   'type' => 'purchase',       'code' => 'COMP', 'document_type_code' => null],
            ['name' => 'Cuadre de Caja',            'type' => 'cash',           'code' => 'CAJA', 'document_type_code' => null],
        ];

        // 2️⃣ Procesa cada diario (sin compañías)
        foreach ($journals as $journalData) {
            // a) Crea la secuencia
            $sequence = Sequence::create([
                'sequence_size' => 8,
                'step'          => 1,
                'next_number'   => 1,
            ]);

            // b) Crea o actualiza el diario sin asociarlo a una compañía
            Journal::updateOrCreate(
                ['code' => $journalData['code']], // búsqueda única por código
                [
                    'name'                => $journalData['name'],
                    'type'                => $journalData['type'],
                    'document_type_code'  => $journalData['document_type_code'],
                    'sequence_id'         => $sequence->id,
                    // 'company_id' => null,   // opcional, no se envía
                ]
            );
        }
    }
}
