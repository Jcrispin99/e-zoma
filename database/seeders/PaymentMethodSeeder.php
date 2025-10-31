<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            ['name' => 'Efectivo', 'is_active' => true],
            ['name' => 'Tarjeta', 'is_active' => true],
            ['name' => 'Yape', 'is_active' => true],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->firstOrCreate(
                ['name' => $method['name']],
                ['is_active' => $method['is_active']]
            );
        }
    }
}
