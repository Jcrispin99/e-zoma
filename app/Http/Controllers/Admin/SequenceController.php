<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sequence;
use Illuminate\Http\Request;

class SequenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.sequences.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.sequences.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:sequences,name',
            'description' => 'nullable|string',
            'prefix' => 'required|string|max:255',
            'sequence_size' => 'required|integer|min:1',
            'step' => 'required|integer|min:1',
            'next_number' => 'required|integer|min:1',
        ]);

        $sequence = Sequence::create($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Secuencia ' . $sequence->name . ' ha sido creada',
        ]);

        return redirect()->route('admin.sequences.edit', $sequence);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sequence $sequence)
    {
        return view('admin.sequences.edit', compact('sequence'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sequence $sequence)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:sequences,name,' . $sequence->id,
            'description' => 'nullable|string',
            'prefix' => 'required|string|max:255',
            'sequence_size' => 'required|integer|min:1',
            'step' => 'required|integer|min:1',
            'next_number' => 'required|integer|min:1',
        ]);

        $sequence->update($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Secuencia ' . $data['name'] . ' ha sido actualizada',
        ]);

        return redirect()->route('admin.sequences.edit', $sequence);
    }

    public function destroy(Sequence $sequence)
    {
        $sequence->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Secuencia ' . $sequence->name . ' ha sido eliminada',
        ]);

        return redirect()->route('admin.sequences.index');
    }
}
