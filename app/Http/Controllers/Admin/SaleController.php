<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.sales.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.sales.create');
    }

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(Sale $sale)
    {
        $pdf = Pdf::loadView('admin.sales.pdf', [
            'sale' => $sale,
        ]);
        return $pdf->download('comprobante-venta-' . $sale->id . '.pdf');
    }
}
