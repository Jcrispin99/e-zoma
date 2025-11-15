<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class QuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_quotes', Quote::class);
        return view('admin.quotes.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_quotes', Quote::class);
        return view('admin.quotes.create');
    }

    public function edit(Quote $quote)
    {
        Gate::authorize('update_quotes', $quote);
        return view('admin.quotes.edit', compact('quote'));
    }

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(Quote $quote)
    {
        Gate::authorize('export_pdf_quotes', $quote);
        $pdf = Pdf::loadView('pdf.quote.show', [
            'quote' => $quote,
            'useLayout' => false,
            'isPublic' => false,
        ]);
        return $pdf->download('cotizacion-' . $quote->id . '.pdf');
    }

    /**
     * Show a styled HTML preview of the Quote PDF with a download button.
     */
    public function pdfView(Quote $quote)
    {
        Gate::authorize('export_pdf_quotes', $quote);
        return view('pdf.quote.show', [
            'quote' => $quote,
            'useLayout' => true,
            'isPublic' => false,
        ]);
    }

    /**
     * Public HTML preview of the Quote PDF (signed URL required).
     */
    public function publicPdfView(Quote $quote)
    {
        Gate::authorize('export_pdf_quotes', $quote);
        return view('pdf.quote.show', [
            'quote' => $quote,
            'useLayout' => false,
            'isPublic' => true,
        ]);
    }
}
