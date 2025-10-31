<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $attributeId)
    {
        return AttributeValue::select('id', 'value', 'attribute_id')
            ->where('attribute_id', $attributeId)
            ->when($request->search, function ($query, $search) {
                $query->where('value', 'like', "%{$search}%");
            })
            ->when(
                $request->exists('selected'),
                fn($query) => $query->whereIn('id', $request->input('selected', [])),
                fn($query) => $query->limit(20)
            )
            ->get();
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
    public function show(AttributeValue $atributeValue)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AttributeValue $atributeValue)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AttributeValue $atributeValue)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttributeValue $atributeValue)
    {
        //
    }
}
