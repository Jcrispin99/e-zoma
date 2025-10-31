<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class SunatConnectionController extends Controller
{
    public function index()
    {
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
        return $this->store($request);
    }
}
