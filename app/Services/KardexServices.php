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
        $newQuantityBalance = $lastRecord['cuantity'] + $variant['quantity'];
        $newTotalBalance = $lastRecord['total'] + ($variant['quantity'] * $variant['price']);
        $newCostBalance = $newTotalBalance / ($newQuantityBalance ?: 1);

        $model->inventories()->create([
            'detail' => $detail,
            'quantity_in' => $variant['quantity'],
            'cost_in' => $variant['price'],
            'total_in' => $variant['quantity'] * $variant['price'],
            'quantity_balance' => $newQuantityBalance,
            'cost_balance' => $newCostBalance,
            'total_balance' => $newTotalBalance,
            'variant_id' => $variant['id'],
            'warehouse_id' => $warehouse_id,
        ]);
        Variant::where('id', $variant['id'])->increment('stock', $variant['quantity']);
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
