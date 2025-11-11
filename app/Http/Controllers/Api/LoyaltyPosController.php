<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyEarnRule;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyAccount;
use App\Models\Customer;

class LoyaltyPosController extends Controller
{
    public function config(Request $request)
    {
        $program = LoyaltyProgram::query()
            ->where('is_active', true)
            ->where('type', 'points')
            ->whereIn('scope', ['pos', 'both'])
            ->where(function ($q) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', now());
            })
            ->orderBy('id')
            ->first();

        $activeForPos = $program !== null;

        $earnPerSol = 0.0;
        $canRedeem = false;
        $solesPerPoint = 0.0;
        $maxDiscountAmount = null;

        if ($activeForPos) {
            // Regla de acumulación por monto (scope all)
            $earnRule = LoyaltyEarnRule::query()
                ->where('program_id', $program->id)
                ->where('is_active', true)
                ->where('basis', 'per_amount')
                ->where(function ($q) {
                    $q->whereNull('scope_type')->orWhere('scope_type', 'all');
                })
                ->orderByDesc('priority')
                ->first();

            if ($earnRule && (float) ($earnRule->points_per_sol ?? 0) > 0) {
                $earnPerSol = (float) ($earnRule->points_per_sol ?? 0);
            }

            // Recompensa de descuento por puntos a nivel de orden
            $reward = LoyaltyReward::query()
                ->where('program_id', $program->id)
                ->where('is_active', true)
                ->where('reward_type', 'discount')
                ->where('discount_method', 'soles_per_point')
                ->where(function ($q) {
                    $q->whereNull('discount_scope')->orWhere('discount_scope', 'order');
                })
                ->orderByDesc('priority')
                ->first();

            if ($reward && (float) ($reward->soles_per_point ?? 0) > 0) {
                $canRedeem = true;
                $solesPerPoint = (float) ($reward->soles_per_point ?? 0);
                $maxDiscountAmount = $reward->max_discount_amount;
            }
        }

        return response()->json([
            'program_id' => $program?->id,
            'active_for_pos' => $activeForPos,
            'earn_per_sol' => $earnPerSol,
            'soles_per_point' => $solesPerPoint,
            'max_discount_amount' => $maxDiscountAmount,
            'can_redeem' => $canRedeem,
        ]);
    }

    public function account(Customer $customer)
    {
        // Cuenta de lealtad del cliente
        $account = LoyaltyAccount::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->first();

        // Si no hay cuenta, devolver valores por defecto
        if (!$account) {
            return response()->json([
                'customer_id' => $customer->id,
                'points_balance' => 0,
                'status' => 'inactive',
            ]);
        }

        return response()->json([
            'customer_id' => $customer->id,
            'points_balance' => round((float) ($account->points_balance ?? 0), 2),
            'status' => $account->status,
        ]);
    }
}
