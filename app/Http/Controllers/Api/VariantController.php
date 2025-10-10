<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use Illuminate\Http\Request;

class VariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Variant::query()
            ->select('variants.id', 'products.name as name')
            ->join('products', 'products.id', '=', 'variants.product_id')
            ->when($request->search, function ($query, $search) {
                $query->where('products.name', 'like', "%{$search}%")
                    ->orWhere('variants.sku', 'like', "%{$search}%");
            })
            ->when(
                $request->exists('selected'),
                fn($query) => $query->whereIn('variants.id', $request->input('selected', [])),
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
    public function show(Variant $variant)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Variant $variant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Variant $variant)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Variant $variant)
    {
        //
    }
}
