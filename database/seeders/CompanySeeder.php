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
        $parent = Company::updateOrCreate(
            [
                'document_number' => '20614550440',
            ],
            [
                'name' => 'KOODI SOLUTIONS S.A.C.',
                'trade_name' => 'IKOO DEV',
                'identity_id' => 3,
                'address' => 'Jr. Callao Nro. 545 Tingo Maria',
                'email' => 'contacto@koodi.com',
                'phone' => '987654321',
                'tax_address' => 'Jr. Callao Nro. 545 Tingo Maria',
                'legal_representative' => 'Jhamil Crispin',
                'district_id' => '010101',
            ]
        );

        Company::updateOrCreate(
            [
                'parent_id' => $parent->id,
                'name' => 'Sucursal Arequipa',
            ],
            [
                'trade_name' => '',
                'address' => 'Av. Arequipa 456, Arequipa',
                'email' => 'contacto@sucursal-aqp.com',
                'phone' => '987654322',
            ]
        );
    }
}
