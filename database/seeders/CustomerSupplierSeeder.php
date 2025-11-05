<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Identity;
use App\Models\Customer;
use App\Models\Supplier;

class CustomerSupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtiene IDs para DNI y RUC
        $dni = Identity::where('name', 'DNI')->first();
        $ruc = Identity::where('name', 'RUC')->first();

        // Cliente específico (DNI)
        Customer::updateOrCreate(
            ['document_number' => '75003828'],
            [
                'identity_id' => optional($dni)->id ?? 2,
                'name' => 'Jhamil antony crispin martel',
                'address' => 'Lima, Perú',
                'email' => 'jhamil@example.com',
                'phone' => '999999999',
            ]
        );

        // Proveedor con RUC de empresa conocida
        Supplier::updateOrCreate(
            ['document_number' => '20100017491'], // TELEFÓNICA DEL PERÚ S.A.A.
            [
                'identity_id' => optional($ruc)->id ?? 3,
                'name' => 'TELEFÓNICA DEL PERÚ S.A.A.',
                'address' => 'Av. Arequipa 1155, Lima',
                'email' => 'facturacion@telefonica.com.pe',
                'phone' => '014444444',
            ]
        );
    }
}
