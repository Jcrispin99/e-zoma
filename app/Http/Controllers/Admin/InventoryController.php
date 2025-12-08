<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Variant;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'products_count' => Product::count(),
            'variants_count' => Variant::count(),
            'warehouses_count' => Warehouse::count(),
            'categories_count' => Category::count(),
            'attributes_count' => Attribute::count(),
            'total_stock' => Variant::sum('stock'),
        ];

        $categoryDistribution = Category::withCount('products')
            ->orderByDesc('products_count')
            ->take(8)
            ->get()
            ->map(function ($category) {
                return [
                    'name' => $category->name,
                    'value' => $category->products_count
                ];
            });

        $warehouseStock = DB::table('inventories')
            ->join('warehouses', 'inventories.warehouse_id', '=', 'warehouses.id')
            ->select('warehouses.name', DB::raw('SUM(IFNULL(quantity_in, 0) - IFNULL(quantity_out, 0)) as total_stock'))
            ->groupBy('warehouses.id', 'warehouses.name')
            ->having('total_stock', '>', 0)
            ->get();

        $recentProducts = Product::with('category', 'variants')->latest()->take(5)->get();

        return Inertia::render('inventory/Index', [
            'stats' => $stats,
            'categoryDistribution' => $categoryDistribution,
            'warehouseStock' => $warehouseStock,
            'recentProducts' => $recentProducts
        ]);
    }
}
