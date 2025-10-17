<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Warehouse::create([
            'name' => 'IK01',
            'location' => 'Calle Principal 123, Ciudad',
            'company_id' => 1,
        ]);
        Warehouse::create([
            'name' => 'IK02',
            'location' => 'Avenida Secundaria 456, Ciudad',
            'company_id' => 2,
        ]);
    }
}
