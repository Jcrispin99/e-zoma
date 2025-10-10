<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $selectedCompanyIds = $request->input('company_ids', []);
        return Warehouse::select('id', 'name', 'location as description')
            ->when(!empty($selectedCompanyIds), function ($query) use ($selectedCompanyIds) {
                $query->whereIn('company_id', $selectedCompanyIds);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "{$search}")
                    ->orWhere('location', 'like', "{$search}");
            })
            ->when($request->exclude, function ($query, $exclude) {
                $query->where('id', '!=', $exclude);
            })
            ->when(
                $request->exists('selected'),
                fn($query) => $query->whereIn('id', $request->input('selected', [])),
                fn($query) => $query->limit(10)
            )->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Warehouse $warehouse)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        //
    }
}
