<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoyaltyProgram;

class LoyaltyProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LoyaltyProgram::firstOrCreate(
            ['code' => 'POS-POINTS'],
            [
                'name' => 'Programa de Puntos POS',
                'type' => 'points',
                'scope' => 'pos',
                'is_active' => true,
                'valid_from' => null,
                'valid_to' => null,
            ]
        );
    }
}
