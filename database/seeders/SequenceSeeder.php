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
        $companies = Company::all();

        foreach ($companies as $company) {
            $journals = [
                ['name' => 'Cotizaciones', 'type' => 'quote', 'code' => 'COT'],
                ['name' => 'Factura F001', 'type' => 'sale', 'code' => 'F001'],
                ['name' => 'Boleta B001', 'type' => 'sale', 'code' => 'B001'],
                ['name' => 'Órdenes de Compra', 'type' => 'purchase-order', 'code' => 'OC'],
                ['name' => 'Compras', 'type' => 'purchase', 'code' => 'COMP'],
                ['name' => 'Cuadre de Caja', 'type' => 'cash', 'code' => 'CAJA'],
            ];

            foreach ($journals as $journalData) {
                // Crear una secuencia simple para cada diario
                $sequence = Sequence::create([
                    'sequence_size' => 8,
                    'step' => 1,
                    'next_number' => 1,
                ]);

                // Crear el diario y asociarlo a la nueva secuencia
                Journal::create([
                    'name' => $journalData['name'],
                    'type' => $journalData['type'],
                    'code' => $journalData['code'],
                    'sequence_id' => $sequence->id,
                    'company_id' => $company->id,
                ]);
            }
        }
    }
}
