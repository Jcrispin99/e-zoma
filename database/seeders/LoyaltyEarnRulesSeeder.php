<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyEarnRule;

class LoyaltyEarnRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $program = LoyaltyProgram::where('code', 'POS-POINTS')->first();
        if (!$program) {
            return; // program must exist
        }

        // Regla principal: acumular por monto gastado
        LoyaltyEarnRule::firstOrCreate(
            [
                'program_id' => $program->id,
                'name' => 'Acumulación por monto',
                'basis' => 'per_amount',
                'scope_type' => 'all',
            ],
            [
                'points_per_sol' => 0.03,
                'points_per_unit' => null,
                'points_per_order' => null,
                'min_qty' => null,
                'min_amount' => null,
                'is_active' => true,
                'priority' => 100,
                'description' => 'Acumula puntos por cada sol gastado en el pedido.',
                'category_id' => null,
            ]
        );
    }
}
