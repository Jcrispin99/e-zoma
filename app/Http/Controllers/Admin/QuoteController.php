<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class QuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.quotes.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.quotes.create');
    }

    public function edit(Quote $quote)
    {
        return view('admin.quotes.edit', compact('quote'));
    }

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(Quote $quote)
    {
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
        return view('pdf.quote.show', [
            'quote' => $quote,
            'useLayout' => false,
            'isPublic' => true,
        ]);
    }
}
