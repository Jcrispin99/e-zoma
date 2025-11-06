<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Variant;

class KardexServices
{
    public function getLastRecord($variant_id, $warehouse_id)
    {
        $lastRecord = Inventory::where('variant_id', $variant_id)
            ->where('warehouse_id', $warehouse_id)
            ->latest()
            ->first();
        return [
            'cuantity' => $lastRecord?->quantity_balance ?? 0,
            'cost' => $lastRecord?->cost_balance ?? 0,
            'total' => $lastRecord?->total_balance ?? 0,
            'date' => $lastRecord?->created_at ?? null,
        ];
    }
    public function registerEntry($model, array $variant, $warehouse_id, $detail)
    {
        $lastRecord = $this->getLastRecord($variant['id'], $warehouse_id);

        $qty = (float) ($variant['quantity'] ?? 0);
        // Normalizar costo unitario: si viene "subtotal" (monto base sin IGV) y es > 0, usarlo.
        $baseSubtotal = $variant['subtotal'] ?? null;
        $unitCost = ($baseSubtotal !== null && (float) $baseSubtotal > 0 && $qty > 0)
            ? ((float) $baseSubtotal / $qty)
            : (float) ($variant['price'] ?? 0);

        $newQuantityBalance = $lastRecord['cuantity'] + $qty;
        $newTotalBalance = $lastRecord['total'] + ($qty * $unitCost);
        $newCostBalance = $newTotalBalance / ($newQuantityBalance ?: 1);

        $model->inventories()->create([
            'detail' => $detail,
            'quantity_in' => $qty,
            'cost_in' => $unitCost,
            'total_in' => $qty * $unitCost,
            'quantity_balance' => $newQuantityBalance,
            'cost_balance' => $newCostBalance,
            'total_balance' => $newTotalBalance,
            'variant_id' => $variant['id'],
            'warehouse_id' => $warehouse_id,
        ]);
        Variant::where('id', $variant['id'])->increment('stock', $qty);
    }
    public function registerExit($model, array $variant, $warehouse_id, $detail)
    {
        $lastRecord = $this->getLastRecord($variant['id'], $warehouse_id);
        $newQuantityBalance = $lastRecord['cuantity'] - $variant['quantity'];
        $newTotalBalance = $lastRecord['total'] - ($variant['quantity'] * $lastRecord['cost']);
        $newCostBalance = $newTotalBalance / ($newQuantityBalance ?: 1);

        $model->inventories()->create([
            'detail' => $detail,
            'quantity_out' => $variant['quantity'],
            'cost_out' => $lastRecord['cost'],
            'total_out' => $variant['quantity'] * $lastRecord['cost'],
            'quantity_balance' => $newQuantityBalance,
            'cost_balance' => $newCostBalance,
            'total_balance' => $newTotalBalance,
            'variant_id' => $variant['id'],
            'warehouse_id' => $warehouse_id,
        ]);
        Variant::where('id', $variant['id'])->decrement('stock', $variant['quantity']);
    }
}
