<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Models\Identity;
use App\Models\UbigeoDepartment;
use App\Models\UbigeoProvince;
use App\Models\UbigeoDistrict;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class Companycontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_companies', Company::class);
        return view('admin.companies.index', [
            'companies' => Company::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_companies', Company::class);
        $identities = Identity::select('id', 'name')->get();
        $parentCompanies = Company::query()
            ->where('is_active', true)
            ->select('id', 'name')
            ->get();

        // Ubigeo: Departamentos para selects dependientes
        $departments = UbigeoDepartment::select('id', 'name')->orderBy('name')->get();

        return view('admin.companies.create', compact('identities', 'parentCompanies', 'departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create_companies', Company::class);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'identity_id' => 'required|exists:identities,id',
            'document_number' => 'required|string|max:255|unique:companies,document_number',
            'address' => 'nullable|string|max:255',
            // Ubigeo: solo persistimos district_id
            'district_id' => 'nullable|string|size:6|exists:ubigeo_districts,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'tax_address' => 'nullable|string|max:255',
            'legal_representative' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:companies,id',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'policies' => 'nullable|string',
            'slogan' => 'nullable|string|max:255',
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
        Gate::authorize('update_companies', $company);
        $identities = Identity::select('id', 'name')->get();
        $parentCompanies = Company::query()
            ->where('is_active', true)
            ->where('id', '!=', $company->id) // Exclude self
            ->where('id', '!=', $company->id) // Evita que una compañía sea su propio padre
            ->select('id', 'name')
            ->get();

        // Ubigeo inicial para edición
        $departments = UbigeoDepartment::select('id', 'name')->orderBy('name')->get();
        $district = $company->district_id ? UbigeoDistrict::find($company->district_id) : null;
        $selectedDepartmentId = $district ? $district->department_id : null;
        $selectedProvinceId = $district ? $district->province_id : null;
        $provinces = $selectedDepartmentId
            ? UbigeoProvince::where('department_id', $selectedDepartmentId)->select('id', 'name')->orderBy('name')->get()
            : collect();
        $districts = $selectedProvinceId
            ? UbigeoDistrict::where('province_id', $selectedProvinceId)->select('id', 'name')->orderBy('name')->get()
            : collect();

        // Enfoque alternativo: cargar todo y filtrar en el cliente (sin llamadas API)
        $allProvinces = UbigeoProvince::select('id', 'name', 'department_id')->orderBy('name')->get();
        $allDistricts = UbigeoDistrict::select('id', 'name', 'province_id')->orderBy('name')->get();

        return view(
            'admin.companies.edit',
            compact(
                'company',
                'identities',
                'parentCompanies',
                'departments',
                'provinces',
                'districts',
                'selectedDepartmentId',
                'selectedProvinceId',
                'allProvinces',
                'allDistricts'
            )
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
        Gate::authorize('update_companies', $company);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'identity_id' => 'required|exists:identities,id',
            'document_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies')->ignore($company->id),
            ],
            'address' => 'nullable|string|max:255',
            // Ubigeo: solo persistimos district_id
            'district_id' => 'nullable|string|size:6|exists:ubigeo_districts,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'tax_address' => 'nullable|string|max:255',
            'legal_representative' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:companies,id',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'policies' => 'nullable|string',
            'slogan' => 'nullable|string|max:255',
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
        ]);

        return redirect()->route('admin.companies.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        Gate::authorize('delete_companies', $company);
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

    /**
     * Endpoints Ubigeo para selects dependientes
     */
    public function ubigeoProvinces(Request $request)
    {
        $departmentId = $request->query('department_id');
        if (!$departmentId) {
            return response()->json([]);
        }
        $provinces = UbigeoProvince::where('department_id', $departmentId)
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($provinces);
    }

    public function ubigeoDistricts(Request $request)
    {
        $provinceId = $request->query('province_id');
        if (!$provinceId) {
            return response()->json([]);
        }
        $districts = UbigeoDistrict::where('province_id', $provinceId)
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($districts);
    }
}
