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

    public function getVariantsPos(Request $request)
    {
        $variants = Variant::query()
            ->with(['product', 'attributeValues', 'images'])
            ->when($request->search, function ($query, $search) {
                $query->where('barcode', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('attributeValues', function ($q) use ($search) {
                        $q->where('value', 'like', "%{$search}%");
                    });
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->whereHas('product', function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            })
            ->when(
                $request->exists('selected'),
                fn($query) => $query->whereIn('variants.id', $request->input('selected', [])),
                fn($query) => $query->limit(24)
            )->get();

        return $variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'name' => $variant->fullName,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'stock' => $variant->stock,
                'image' => $variant->image(),
            ];
        });
    }
}
