<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_purchase-orders', PurchaseOrder::class);
        return view('admin.purchases-orders.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_purchase-orders', PurchaseOrder::class);
        return view('admin.purchases-orders.create');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('update_purchase-orders', $purchaseOrder);
        return view('admin.purchases-orders.edit', compact('purchaseOrder'));
    }

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('export_pdf_purchase-orders', $purchaseOrder);
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
        Gate::authorize('export_pdf_purchase-orders', $purchaseOrder);
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
        Gate::authorize('export_pdf_purchase-orders', $purchaseOrder);
        // Vista pública mínima sin layout admin (URL firmada)
        return view('pdf.purchase-order.show', [
            'po' => $purchaseOrder,
            'useLayout' => false,
            'isPublic' => true,
        ]);
    }
}
