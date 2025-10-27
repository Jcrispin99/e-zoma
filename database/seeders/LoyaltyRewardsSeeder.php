<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyReward;

class LoyaltyRewardsSeeder extends Seeder
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

        // Recompensa: descuento en orden basado en puntos
        LoyaltyReward::firstOrCreate(
            [
                'program_id' => $program->id,
                'name' => 'Descuento por puntos',
                'reward_type' => 'discount',
                'discount_method' => 'soles_per_point',
                'discount_scope' => 'order',
            ],
            [
                'points_cost' => 0,
                'consume_all_points' => true,
                'reward_product_id' => null,
                'discount_percent' => null,
                'soles_per_point' => 1,
                'fixed_amount' => null,
                'max_discount_amount' => 50.00,
                'description' => 'Convierte puntos en descuento sobre el total de la orden.',
                'is_active' => true,
                'priority' => 100,
            ]
        );
    }
}
