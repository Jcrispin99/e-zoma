<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'comercial_name' => 'Empresa Principal',
                'legal_name' => 'Empresa Principal S.A.',
                'vat' => '12345678901',
                'address' => 'Av. Principal 123, Ciudad',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'comercial_name' => 'Sucursal Norte',
                'legal_name' => 'Sucursal Norte S.R.L.',
                'vat' => '12345678902',
                'address' => 'Calle Norte 456, Ciudad Norte',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'comercial_name' => 'Sucursal Sur',
                'legal_name' => 'Sucursal Sur S.A.C.',
                'vat' => '12345678903',
                'address' => 'Av. Sur 789, Ciudad Sur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}
