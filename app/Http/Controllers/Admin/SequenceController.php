<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SequenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_sequences', Sequence::class);
        return view('admin.sequences.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_sequences', Sequence::class);
        return view('admin.sequences.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create_sequences', Sequence::class);
        $data = $request->validate([
            'sequence_size' => 'required|integer|min:1',
            'step' => 'required|integer|min:1',
            'next_number' => 'required|integer|min:1',
        ]);

        $sequence = Sequence::create($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Secuencia ha sido creada',
        ]);

        return redirect()->route('admin.sequences.edit', $sequence);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sequence $sequence)
    {
        Gate::authorize('update_sequences', $sequence);
        return view('admin.sequences.edit', compact('sequence'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sequence $sequence)
    {
        Gate::authorize('update_sequences', $sequence);
        $data = $request->validate([
            'sequence_size' => 'required|integer|min:1',
            'step' => 'required|integer|min:1',
            'next_number' => 'required|integer|min:1',
        ]);

        $sequence->update($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Secuencia ha sido actualizada',
        ]);

        return redirect()->route('admin.sequences.edit', $sequence);
    }

    public function destroy(Sequence $sequence)
    {
        Gate::authorize('delete_sequences', $sequence);
        $sequence->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Secuencia ha sido eliminada',
        ]);

        return redirect()->route('admin.sequences.index');
    }
}
