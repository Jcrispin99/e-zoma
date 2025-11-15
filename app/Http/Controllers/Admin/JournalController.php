<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JournalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_journals', Journal::class);
        return view('admin.journals.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_journals', Journal::class);
        $companies = Company::all();
        $sequences = Sequence::all();
        return view('admin.journals.create', compact('companies', 'sequences'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create_journals', Journal::class);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
            'is_fiscal' => 'nullable|boolean',
            'document_type_code' => 'nullable|string|in:01,03,07,08|required_if:is_fiscal,1',
        ]);

        // Normalizar booleano y coherencia del tipo de documento
        $data['is_fiscal'] = $request->boolean('is_fiscal');
        if (! $data['is_fiscal']) {
            $data['document_type_code'] = null;
        }

        $sequence = Sequence::create([
            'sequence_size' => 9,
            'step' => 1,
            'next_number' => 1,
        ]);

        $data['sequence_id'] = $sequence->id;

        $journal = Journal::create($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Diario ha sido creado',
        ]);

        return redirect()->route('admin.journals.edit', $journal);
    }

    /**
     * Display the specified resource.
     */
    public function show(Journal $journal)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Journal $journal)
    {
        Gate::authorize('update_journals', $journal);
        $companies = Company::all();
        $sequences = Sequence::all();
        return view('admin.journals.edit', compact('journal', 'companies', 'sequences'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Journal $journal)
    {
        Gate::authorize('update_journals', $journal);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'sequence_id' => 'required|exists:sequences,id',
            'company_id' => 'required|exists:companies,id',
            'is_fiscal' => 'nullable|boolean',
            'document_type_code' => 'nullable|string|in:01,03,07,08',
        ]);

        // Bloqueo: si la secuencia ya avanzó (>= 2), no permitir cambiar is_fiscal
        $lockedFiscal = optional($journal->sequence)->next_number >= 2;
        $requestedFiscal = $request->boolean('is_fiscal');
        $effectiveFiscal = $lockedFiscal ? $journal->is_fiscal : $requestedFiscal;

        // Validar coherencia del tipo de documento respecto al estado fiscal efectivo
        if ($effectiveFiscal && empty($data['document_type_code'])) {
            return back()->withErrors(['document_type_code' => 'Seleccione el tipo de documento SUNAT para diarios fiscales.'])->withInput();
        }

        // Normalizar y coherencia del tipo de documento
        $data['is_fiscal'] = $effectiveFiscal;
        if (! $effectiveFiscal) {
            $data['document_type_code'] = null;
        }

        $journal->update($data);

        if ($lockedFiscal && $requestedFiscal !== $journal->is_fiscal) {
            session()->flash('swalt', [
                'icon' => 'warning',
                'title' => 'Configuración fiscal bloqueada',
                'text' => 'No se puede modificar "Documento fiscal" porque la secuencia ya tiene movimientos.',
            ]);
        } else {
            session()->flash('swalt', [
                'icon' => 'success',
                'title' => '¡Bien hecho!',
                'text' => 'Diario ha sido actualizado',
            ]);
        }

        return redirect()->route('admin.journals.edit', $journal);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Journal $journal)
    {
        Gate::authorize('delete_journals', $journal);
        $journal->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Diario ha sido eliminado',
        ]);

        return redirect()->route('admin.journals.index');
    }
}
