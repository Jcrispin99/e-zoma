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
        $variants = Variant::query()
            ->with(['product', 'attributeValues'])
            ->when($request->search, function ($query, $search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('attributeValues', function ($q) use ($search) {
                        $q->where('value', 'like', "%{$search}%");
                    });
            })
            ->when(
                $request->exists('selected'),
                fn($query) => $query->whereIn('variants.id', $request->input('selected', [])),
                fn($query) => $query->limit(10)
            )->get();

        return $variants->map(function ($variant) {
            return ['id' => $variant->id, 'name' => $variant->fullName];
        });
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
