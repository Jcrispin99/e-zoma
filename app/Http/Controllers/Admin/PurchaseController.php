<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.purchases.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.purchases.create');
    }

    public function pdf(Purchase $purchase)
    {
        $pdf = Pdf::loadView('admin.purchases.pdf', [
            'purchase' => $purchase,
        ]);
        return $pdf->download('factura-' . $purchase->id . '.pdf');
    }
}
