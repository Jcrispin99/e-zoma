<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $purchaseOrders = PurchaseOrder::when($request->search, function ($query, $search) {

            //OC-123
            $parts = explode('-', $search);

            if (count($parts) == 1) {
                //buscar por nombre de proveedor
                $query->whereHas('supplier', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                });
                return;
            }

            if (count($parts) == 2) {

                $serie = $parts[0];
                $correlative = ltrim($parts[1], '0');

                $query->where('serie', $serie)
                    ->where('correlative', 'LIKE', "%{$correlative}%");
                return;
            }
        })
            ->when(
                $request->exists('selected'),
                fn($query) => $query->whereIn('id', $request->input('selected', [])),
                fn($query) => $query->limit(10)
            )
            ->with(['supplier'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $purchaseOrders->map(function ($purchaseOrder) {
            return [
                'id' => $purchaseOrder->id,
                'name' => $purchaseOrder->serie . '-' . $purchaseOrder->correlative,
                'description' => $purchaseOrder->supplier->name . ' - ' . $purchaseOrder->supplier->document_number,
            ];
        });
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        //
    }
}
