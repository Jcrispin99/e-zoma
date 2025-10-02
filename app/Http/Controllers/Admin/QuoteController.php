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

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(Quote $quote)
    {
        $pdf = Pdf::loadView('admin.quotes.pdf', [
            'model' => $quote,
        ]);
        return $pdf->download('cotizacion-' . $quote->id . '.pdf');
    }
}
