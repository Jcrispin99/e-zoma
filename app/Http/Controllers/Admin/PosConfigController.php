<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Journal;
use App\Models\PosConfig;
use App\Models\Tax;
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
        $activeCompanyId = session('active_company_id');
        $warehousesQuery = Warehouse::query();
        if ($activeCompanyId) {
            $warehousesQuery->where('company_id', $activeCompanyId);
        }
        $warehouses = $warehousesQuery->get();
        $defaultWarehouseId = optional($warehouses->first())->id;
        $customers = Customer::all();
        $journals = Journal::where('type', 'sale')->with('sequence')->get();
        $taxes = Tax::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('admin.posconfig.create', compact('companies', 'warehouses', 'customers', 'journals', 'taxes', 'defaultWarehouseId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_journal_id' => 'required|exists:journals,id',
            'invoice_journal_id' => 'required|exists:journals,id',
            'default_customer_id' => 'required|exists:customers,id',
            'default_tax_id' => 'nullable|exists:taxes,id',
            'is_active' => 'boolean',
            // IGV
            'apply_tax' => 'boolean',
            'tax_rate' => 'numeric|min:0|max:1',
            'tax_rate_preset' => 'nullable|in:0,0.18',
            'prices_include_tax' => 'boolean',
        ]);

        $data['apply_tax'] = $request->boolean('apply_tax');
        $data['prices_include_tax'] = $request->boolean('prices_include_tax');

        if ($request->filled('tax_rate_preset')) {
            $preset = $request->input('tax_rate_preset');
            $data['tax_rate'] = $preset === '0.18' ? 0.18 : 0.0;
            $data['apply_tax'] = $preset !== '0';
        }

        // Asignar compañía desde sesión
        $activeCompanyId = session('active_company_id');
        if (!$activeCompanyId) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear la configuración POS.',
            ]);
            return redirect()->back();
        }

        $data['company_id'] = $activeCompanyId;

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
        // Filtrar almacenes por la compañía del POS config (no se edita compañía aquí)
        $warehouses = Warehouse::query()
            ->where('company_id', $posConfig->company_id)
            ->get();
        $customers = Customer::all();
        $journals = Journal::where('type', 'sale')->with('sequence')->get();
        $taxes = Tax::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('admin.posconfig.edit', compact('posConfig', 'warehouses', 'customers', 'journals', 'taxes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PosConfig $posConfig)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_journal_id' => 'required|exists:journals,id',
            'invoice_journal_id' => 'required|exists:journals,id',
            'default_customer_id' => 'required|exists:customers,id',
            'default_tax_id' => 'nullable|exists:taxes,id',
            'is_active' => 'boolean',
            // IGV
            'apply_tax' => 'boolean',
            'tax_rate' => 'numeric|min:0|max:1',
            'tax_rate_preset' => 'nullable|in:0,0.18',
            'prices_include_tax' => 'boolean',
        ]);

        $data['apply_tax'] = $request->boolean('apply_tax');
        $data['prices_include_tax'] = $request->boolean('prices_include_tax');

        if ($request->filled('tax_rate_preset')) {
            $preset = $request->input('tax_rate_preset');
            $data['tax_rate'] = $preset === '0.18' ? 0.18 : 0.0;
            $data['apply_tax'] = $preset !== '0';
        }

        $posConfig->update($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Configuración de POS ha sido actualizada',
        ]);

        return redirect()->route('admin.posconfig.edit', $posConfig);
    }
}
