<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use App\Models\Quote;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;


class MovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_movements', Movement::class);
        return view('admin.movements.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_movements', Movement::class);
        return view('admin.movements.create');
    }

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(Movement $movement)
    {
        Gate::authorize('export_pdf_movements', $movement);
        $pdf = Pdf::loadView('admin.movements.pdf', [
            'model' => $movement,
        ]);
        return $pdf->download('movimiento-' . $movement->id . '.pdf');
    }
}
