<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LoyaltyProgram;
use Illuminate\Support\Facades\Gate;

class LoyaltyProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_loyalty-programs', LoyaltyProgram::class);
        return view('admin.loyalty-programs.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_loyalty-programs', LoyaltyProgram::class);
        return view('admin.loyalty-programs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create_loyalty-programs', LoyaltyProgram::class);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $program = LoyaltyProgram::create($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Programa de lealtad ha sido creado',
        ]);

        return redirect()->route('admin.loyalty-programs.edit', $program);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LoyaltyProgram $program)
    {
        Gate::authorize('update_loyalty-programs', $program);
        return view('admin.loyalty-programs.edit', compact('program'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LoyaltyProgram $program)
    {
        Gate::authorize('update_loyalty-programs', $program);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $program->update($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Programa de lealtad ha sido actualizado',
        ]);

        return redirect()->route('admin.loyalty-programs.edit', $program);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LoyaltyProgram $program)
    {
        Gate::authorize('delete_loyalty-programs', $program);
        $program->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Programa de lealtad ha sido eliminado',
        ]);

        return redirect()->route('admin.loyalty-programs.index');
    }
}
