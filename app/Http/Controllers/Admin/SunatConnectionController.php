<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SunatConnectionController extends Controller
{
    public function index()
    {
        Gate::authorize('read_sunat-connections');
        $company = Company::first();
        if (!$company) {
            return redirect()->route('admin.companies.index')
                ->with('error', 'Debe crear una compañía primero.');
        }

        $connection = $company->sunatConnection;

        return view('admin.sunat-connections.index', compact('company', 'connection'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create_sunat-connections');
        $validated = $request->validate([
            'token_apiperu' => ['nullable', 'string', 'max:512'],
            'token_ikoodev' => ['nullable', 'string', 'max:512'],
        ]);

        $company = Company::first();
        if (!$company) {
            return redirect()->route('admin.companies.index')
                ->with('error', 'Debe crear una compañía primero.');
        }

        $company->sunatConnection()->updateOrCreate([], $validated);

        return redirect()->route('admin.sunat-connections.index')
            ->with('success', 'Tokens guardados correctamente.');
    }

    public function update(Request $request)
    {
        Gate::authorize('update_sunat-connections');
        return $this->store($request);
    }
}
