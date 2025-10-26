<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Models\Identity;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class Companycontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.companies.index', [
            'companies' => Company::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $identities = Identity::select('id', 'name')->get();
        $parentCompanies = Company::query()
            ->where('is_active', true)
            ->select('id', 'name')
            ->get();

        return view('admin.companies.create', compact('identities', 'parentCompanies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'identity_id' => 'required|exists:identities,id',
            'document_number' => 'required|string|max:255|unique:companies,document_number',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'tax_address' => 'nullable|string|max:255',
            'legal_representative' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:companies,id',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data['is_active'] = $request->has('is_active') ? true : false;

        $company = Company::create($data);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $company->images()->create([
                'path' => $path,
                'size' => $request->file('logo')->getSize(),
            ]);
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Compañía ' . $data['name'] . ' ha sido creada exitosamente',
        ]);

        return redirect()->route('admin.companies.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
        $identities = Identity::select('id', 'name')->get();
        $parentCompanies = Company::query()
            ->where('is_active', true)
            ->where('id', '!=', $company->id) // Exclude self
            ->where('id', '!=', $company->id) // Evita que una compañía sea su propio padre
            ->select('id', 'name')
            ->get();

        return view('admin.companies.edit', compact('company', 'identities', 'parentCompanies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'identity_id' => 'required|exists:identities,id',
            'document_number' => 'required|string|max:255|unique:companies,document_number,' . $company->id,
            'document_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies')->ignore($company->id),
            ],
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'tax_address' => 'nullable|string|max:255',
            'legal_representative' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:companies,id',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data['is_active'] = $request->has('is_active') ? true : false;

        $company->update($data);

        if ($request->hasFile('logo')) {
            $existing = $company->images()->first();
            if ($existing) {
                Storage::disk('public')->delete($existing->path);
                $existing->delete();
            }

            $path = $request->file('logo')->store('logos', 'public');
            $company->images()->create([
                'path' => $path,
                'size' => $request->file('logo')->getSize(),
            ]);
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Compañía ' . $data['name'] . ' ha sido actualizada exitosamente',
            'title' => '¡Actualizado!',
            'text' => 'La compañía ' . $company->name . ' ha sido actualizada exitosamente.',
        ]);

        return redirect()->route('admin.companies.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        if ($company->children()->count() > 0) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se puede eliminar la compañía ' . $company->name . ' porque tiene subcompañías.',
            ]);

            return redirect()->route('admin.companies.index');
        }

        $company->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Compañía ' . $company->name . ' ha sido eliminada exitosamente',
        ]);

        return redirect()->route('admin.companies.index');
    }
}
