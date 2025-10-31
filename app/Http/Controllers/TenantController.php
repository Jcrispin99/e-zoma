<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tenants = Tenant::all();
        return view('tenants.index', compact('tenants'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tenants.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|max:255|unique:tenants',
        ]);

        $tenant = Tenant::create($validated);
        $tenant->domains()->create(['domain' => $validated['id'] . '.' . config('tenancy.central_domains')[0]]);

        return redirect()->route('tenants.index')->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $tenant)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant)
    {
        return view('tenants.edit', compact('tenant'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:255', Rule::unique('tenants')->ignore($tenant->id)],
        ]);

        $tenant->update($validated);

        $tenant->domains()->update([
            'domain' => $validated['id'] . '.' . config('tenancy.central_domains')[0]
        ]);

        return redirect()->route('tenants.index')->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return redirect()->route('tenants.index')->with('success', 'Cliente eliminado exitosamente.');
    }
}
