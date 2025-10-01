<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.purchases-orders.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.purchases-orders.create');
    }

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(PurchaseOrder $purchaseOrder)
    {
        $pdf = Pdf::loadView('admin.purchases-orders.pdf', [
            'purchaseOrder' => $purchaseOrder,
        ]);
        return $pdf->download('comprobante-compra-' . $purchaseOrder->id . '.pdf');
    }
}
