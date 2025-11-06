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
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        return view('admin.purchases-orders.edit', compact('purchaseOrder'));
    }

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(PurchaseOrder $purchaseOrder)
    {
        $pdf = Pdf::loadView('pdf.purchase-order.show', [
            'po' => $purchaseOrder,
            'useLayout' => false,
            'isPublic' => false,
        ]);
        return $pdf->download('comprobante-compra-' . $purchaseOrder->id . '.pdf');
    }

    /**
     * Show a styled HTML preview of the Purchase Order PDF with a download button.
     */
    public function pdfView(PurchaseOrder $purchaseOrder)
    {
        return view('pdf.purchase-order.show', [
            'po' => $purchaseOrder,
            'useLayout' => true,
            'isPublic' => false,
        ]);
    }

    /**
     * Public HTML preview of the Purchase Order PDF (signed URL required).
     */
    public function publicPdfView(PurchaseOrder $purchaseOrder)
    {
        // Vista pública mínima sin layout admin (URL firmada)
        return view('pdf.purchase-order.show', [
            'po' => $purchaseOrder,
            'useLayout' => false,
            'isPublic' => true,
        ]);
    }
}
