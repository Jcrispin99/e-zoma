<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use App\Models\Quote;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


class MovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.movements.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.movements.create');
    }

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(Movement $movement)
    {
        $pdf = Pdf::loadView('admin.movements.pdf', [
            'movement' => $movement,
        ]);
        return $pdf->download('movimiento-' . $movement->id . '.pdf');
    }
}
