<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Sequence;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.journals.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::all();
        $sequences = Sequence::all();
        return view('admin.journals.create', compact('companies', 'sequences'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
        ]);

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
        $companies = Company::all();
        $sequences = Sequence::all();
        return view('admin.journals.edit', compact('journal', 'companies', 'sequences'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Journal $journal)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'sequence_id' => 'required|exists:sequences,id',
            'company_id' => 'required|exists:companies,id',
        ]);

        $journal->update($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Diario ha sido actualizado',
        ]);

        return redirect()->route('admin.journals.edit', $journal);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Journal $journal)
    {
        $journal->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Diario ha sido eliminado',
        ]);

        return redirect()->route('admin.journals.index');
    }
}
