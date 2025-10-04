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
        $parent = Company::create([
            'name' => 'Empresa Principal SAC',
            'trade_name' => 'Empresa Principal',
            'identity_id' => 3, // RUC
            'document_number' => '20000000001',
            'address' => 'Av. Principal 123, Lima',
            'email' => 'contacto@principal.com',
            'phone' => '987654321',
            'tax_address' => 'Av. Principal 123, Lima',
            'legal_representative' => 'Juan Perez',
        ]);

        Company::create([
            'parent_id' => $parent->id,
            'name' => 'Sucursal Arequipa SAC',
            'trade_name' => 'Sucursal Arequipa',
            'identity_id' => 3, // RUC
            'document_number' => '20000000002',
            'address' => 'Av. Arequipa 456, Arequipa',
            'email' => 'contacto@sucursal-aqp.com',
            'phone' => '987654322',
        ]);
    }
}
