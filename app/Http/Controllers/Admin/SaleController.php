<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_sales', Sale::class);
        return view('admin.sales.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_sales', Sale::class);
        return view('admin.sales.create');
    }

    public function edit(Sale $sale)
    {
        Gate::authorize('update_sales', $sale);
        return view('admin.sales.edit', compact('sale'));
    }

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(Sale $sale)
    {
        Gate::authorize('export_pdf_sales', $sale);
        $pdf = Pdf::loadView('pdf.sale.show', [
            'sale' => $sale,
            'useLayout' => false,
            'isPublic' => false,
        ]);
        return $pdf->download('comprobante-venta-' . $sale->id . '.pdf');
    }

    /**
     * Show a styled HTML preview of the Sale PDF with a download button.
     */
    public function pdfView(Sale $sale)
    {
        Gate::authorize('export_pdf_sales', $sale);
        return view('pdf.sale.show', [
            'sale' => $sale,
            'useLayout' => true,
            'isPublic' => false,
        ]);
    }

    /**
     * Public HTML preview of the Sale PDF (signed URL required).
     */
    public function publicPdfView(Sale $sale)
    {
        Gate::authorize('export_pdf_sales', $sale);
        return view('pdf.sale.show', [
            'sale' => $sale,
            'useLayout' => false,
            'isPublic' => true,
        ]);
    }
}
