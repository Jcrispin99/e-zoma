<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Identity;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Aseguramos que existan los tipos de identidad necesarios
        $dniId = Identity::firstOrCreate(['name' => 'DNI'])->id;
        $rucId = Identity::firstOrCreate(['name' => 'RUC'])->id;

        Customer::firstOrCreate(
            ['document_number' => '00000000'],
            [
                'identity_id' => $dniId,
                'name' => 'Varios',
                'address' => null,
                'email' => null,
                'phone' => null,
            ]
        );

        Customer::firstOrCreate(
            ['document_number' => '00000000000'],
            [
                'identity_id' => $rucId,
                'name' => 'Varios',
                'address' => null,
                'email' => null,
                'phone' => null,
            ]
        );
    }
}
