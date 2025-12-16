<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_purchases', Purchase::class);
        return view('admin.purchases.index');
    }

    public function dashboard()
    {
        // Simple dashboard for now
        return Inertia::render('purchases/Index');
    }

    public function edit(Purchase $purchase)
    {
        Gate::authorize('update_purchases', $purchase);
        return view('admin.purchases.edit', compact('purchase'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_purchases', Purchase::class);
        return view('admin.purchases.create');
    }

    public function pdf(Purchase $purchase)
    {
        Gate::authorize('export_pdf_purchases', $purchase);
        $pdf = Pdf::loadView('pdf.purchase.show', [
            'purchase' => $purchase,
            'useLayout' => false,
            'isPublic' => false,
        ]);
        return $pdf->download('factura-' . $purchase->id . '.pdf');
    }

    /**
     * Show a styled HTML preview of the Purchase PDF with a download button.
     */
    public function pdfView(Purchase $purchase)
    {
        Gate::authorize('export_pdf_purchases', $purchase);
        return view('pdf.purchase.show', [
            'purchase' => $purchase,
            'useLayout' => true,
            'isPublic' => false,
        ]);
    }

    /**
     * Public HTML preview of the Purchase PDF (signed URL required).
     */
    public function publicPdfView(Purchase $purchase)
    {
        return view('pdf.purchase.show', [
            'purchase' => $purchase,
            'useLayout' => false,
            'isPublic' => true,
        ]);
    }
}
