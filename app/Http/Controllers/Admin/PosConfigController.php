<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosConfig;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\Sequence;



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
        $sequences = Sequence::all();

        return view('admin.posconfig.create', compact('companies', 'warehouses', 'customers', 'sequences'));
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
            'receipt_sequence_id' => 'required|exists:sequences,id',
            'invoice_sequence_id' => 'required|exists:sequences,id',
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
        $sequences = Sequence::all();

        return view('admin.posconfig.edit', compact('posConfig', 'companies', 'warehouses', 'customers', 'sequences'));
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
            'receipt_sequence_id' => 'required|exists:sequences,id',
            'invoice_sequence_id' => 'required|exists:sequences,id',
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PosConfig $posConfig)
    {
        // Verificar si tiene sesiones relacionadas
        $sessionsCount = $posConfig->sessions()->count();

        if ($sessionsCount > 0) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'No se puede eliminar',
                'text' => "No se puede eliminar la configuración de POS porque tiene {$sessionsCount} sesión(es) relacionada.",
            ]);

            return redirect()->route('admin.posconfig.index');
        }

        try {
            $posConfig->delete();

            session()->flash('swalt', [
                'icon' => 'success',
                'title' => '¡Bien hecho!',
                'text' => 'Configuración de POS ha sido eliminada correctamente',
            ]);
        } catch (\Exception $e) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Error al eliminar la configuración: ' . $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.posconfig.index');
    }
}
