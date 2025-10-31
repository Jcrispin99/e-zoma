<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reason;
use Illuminate\Http\Request;

class ReasonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Reason::select('id', 'name')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when(
                $request->exists('selected'),
                fn($query) => $query->whereIn('id', $request->input('selected', [])),
                fn($query) => $query->limit(10)
            )
            ->where('type', $request->input('type', ''))
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
    public function show(Reason $reason)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reason $reason)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reason $reason)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reason $reason)
    {
        //
    }
}
