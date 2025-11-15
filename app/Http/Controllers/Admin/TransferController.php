<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;


class TransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_transfers', Transfer::class);
        return view('admin.transfers.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_transfers', Transfer::class);
        return view('admin.transfers.create');
    }

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(Transfer $transfer)
    {
        Gate::authorize('export_pdf_transfers', $transfer);
        $pdf = Pdf::loadView('admin.transfers.pdf', [
            'model' => $transfer,
        ]);
        return $pdf->download('transfer-' . $transfer->id . '.pdf');
    }
}
