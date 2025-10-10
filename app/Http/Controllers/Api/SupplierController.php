<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        return Supplier::select('id', 'name')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "{$search}")
                    ->orWhere('document_number', 'like', "{$search}");
            })
            ->when(
                $request->exists('selected'),
                fn($query) => $query->whereIn('id', $request->input('selected', [])),
                fn($query) => $query->limit(10)
            )->get();
    }
}
