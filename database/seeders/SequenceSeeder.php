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

            ['name' => 'NOTA DE VENTA',   'type' => 'sale',           'code' => 'NV',   'document_type_code' => null, 'is_fiscal' => false],
            ['name' => 'FACTURA DE VENTA', 'type' => 'sale',           'code' => 'F002', 'document_type_code' => '01', 'is_fiscal' => true],
            ['name' => 'BOLETA DE VENTA', 'type' => 'sale',           'code' => 'B002', 'document_type_code' => '03', 'is_fiscal' => true],
            ['name' => 'Cotizaciones',    'type' => 'quote',          'code' => 'COT',  'document_type_code' => null, 'is_fiscal' => false],
            ['name' => 'Nota de Crédito Factura', 'type' => 'sale',    'code' => 'FC01', 'document_type_code' => '07', 'is_fiscal' => true],
            ['name' => 'Nota de Crédito Boleta', 'type' => 'sale',    'code' => 'BC01', 'document_type_code' => '07', 'is_fiscal' => true],
            ['name' => 'Nota de Débito Factura', 'type' => 'sale',    'code' => 'FD01', 'document_type_code' => '08', 'is_fiscal' => true],
            ['name' => 'Nota de Débito Boleta',  'type' => 'sale',    'code' => 'BD01', 'document_type_code' => '08', 'is_fiscal' => true],
            ['name' => 'Órdenes de Compra',      'type' => 'purchase-order', 'code' => 'OC',   'document_type_code' => null, 'is_fiscal' => false],
            ['name' => 'Compras',                 'type' => 'purchase',       'code' => 'COMP', 'document_type_code' => null, 'is_fiscal' => false],
            ['name' => 'Cuadre de Caja',          'type' => 'cash',           'code' => 'CAJA', 'document_type_code' => null, 'is_fiscal' => false],
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
                    'is_fiscal'           => $journalData['is_fiscal'] ?? false,
                    'sequence_id'         => $sequence->id,
                    // 'company_id' => null,   // opcional, no se envía
                ]
            );
        }
    }
}
