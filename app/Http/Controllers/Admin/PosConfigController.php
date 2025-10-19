<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Journal;
use App\Models\PosConfig;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class PosConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.posconfig.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::all();
        $warehouses = Warehouse::all();
        $customers = Customer::all();
        $journals = Journal::where('type', 'sale')->with('sequence')->get();

        return view('admin.posconfig.create', compact('companies', 'warehouses', 'customers', 'journals'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_journal_id' => 'required|exists:journals,id',
            'invoice_journal_id' => 'required|exists:journals,id',
            'default_customer_id' => 'required|exists:customers,id',
            'is_active' => 'boolean',
        ]);

        $posConfig = PosConfig::create($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Configuración de POS ha sido creada',
        ]);

        return redirect()->route('admin.posconfig.edit', $posConfig);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PosConfig $posConfig)
    {
        $companies = Company::all();
        $warehouses = Warehouse::all();
        $customers = Customer::all();
        $journals = Journal::where('type', 'sale')->with('sequence')->get();

        return view('admin.posconfig.edit', compact('posConfig', 'companies', 'warehouses', 'customers', 'journals'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PosConfig $posConfig)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_journal_id' => 'required|exists:journals,id',
            'invoice_journal_id' => 'required|exists:journals,id',
            'default_customer_id' => 'required|exists:customers,id',
            'is_active' => 'boolean',
        ]);

        $posConfig->update($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Configuración de POS ha sido actualizada',
        ]);

        return redirect()->route('admin.posconfig.edit', $posConfig);
    }
}
