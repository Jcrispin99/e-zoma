<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


class TransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.transfers.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.transfers.create');
    }

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(Transfer $transfer)
    {
        $pdf = Pdf::loadView('admin.transfers.pdf', [
            'transfer' => $transfer,
        ]);
        return $pdf->download('transfer-' . $transfer->id . '.pdf');
    }
}
