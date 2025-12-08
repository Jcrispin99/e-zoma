<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Variant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LowStockExport;
use App\Exports\ValuationExport;
use App\Exports\KardexExport;

class ReportController extends Controller
{
    public function index()
    {
        $stats = [
            'total_valuation' => Variant::sum(DB::raw('stock * price')),
            'low_stock_count' => Variant::where('stock', '<=', 5)->count(),
            'total_items' => Variant::count(),
            'total_movements' => Inventory::count()
        ];

        return Inertia::render('inventory/reports/Index', [
            'stats' => $stats
        ]);
    }

    public function lowStock(Request $request)
    {
        $threshold = $request->input('threshold', 5);
        $search = $request->input('search');

        $query = Variant::with(['product', 'attributeValues'])
            ->where('stock', '<=', $threshold);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $variants = $query->orderBy('stock')
            ->paginate(80)
            ->withQueryString();

        return Inertia::render('inventory/reports/LowStock', [
            'variants' => $variants,
            'threshold' => $threshold
        ]);
    }

    public function exportLowStock(Request $request)
    {
        $selectedIds = $request->input('selected_ids', []);
        $threshold = $request->input('threshold', 5);
        $search = $request->input('search');

        $query = Variant::with(['product', 'attributeValues']);

        if (!empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        } else {
            $query->where('stock', '<=', $threshold);
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }
        }

        $variants = $query->get();
        $filename = 'reporte_bajo_stock_' . now()->timestamp . '.xlsx';

        Excel::store(new LowStockExport($variants), 'reports/' . $filename, 'public');
        $url = Storage::url('reports/' . $filename);

        return back()->with('success', 'Reporte generado correctamente.')->with('download_url', $url);
    }

    public function valuation(Request $request)
    {
        $search = $request->input('search');
        $totalValuation = Variant::sum(DB::raw('stock * price'));

        $lowStockCount = Variant::where('stock', '<=', 5)->count();

        $query = Variant::with(['product', 'attributeValues'])
            ->select('*', DB::raw('(stock * price) as valuation'));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $variants = $query->orderByDesc('valuation')
            ->paginate(80)
            ->withQueryString();

        return Inertia::render('inventory/reports/Valuation', [
            'totalValuation' => $totalValuation,
            'lowStockCount' => $lowStockCount,
            'variants' => $variants
        ]);
    }

    public function exportValuation(Request $request)
    {
        $selectedIds = $request->input('selected_ids', []);
        $search = $request->input('search');

        $query = Variant::with(['product', 'attributeValues']);

        if (!empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        } else {
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }
        }

        $variants = $query->get();
        $filename = 'reporte_valorizacion_' . now()->timestamp . '.xlsx';

        Excel::store(new ValuationExport($variants), 'reports/' . $filename, 'public');
        $url = Storage::url('reports/' . $filename);

        return back()->with('success', 'Reporte generado correctamente.')->with('download_url', $url);
    }

    public function kardex(Request $request)
    {
        $query = Inventory::with(['variant.product', 'variant.attributeValues', 'warehouse'])
            ->latest();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('variant', function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $inventories = $query->paginate(80);

        return Inertia::render('inventory/reports/Kardex', [
            'inventories' => $inventories
        ]);
    }

    public function exportKardex(Request $request)
    {
        $selectedIds = $request->input('selected_ids', []);
        $search = $request->input('search');

        $query = Inventory::with(['variant.product', 'variant.attributeValues', 'warehouse'])
            ->latest();

        if (!empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        } else {
            if ($search) {
                $query->whereHas('variant', function ($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }
        }

        $inventories = $query->get();
        $filename = 'reporte_kardex_' . now()->timestamp . '.xlsx';

        Excel::store(new KardexExport($inventories), 'reports/' . $filename, 'public');
        $url = Storage::url('reports/' . $filename);

        return back()->with('success', 'Reporte generado correctamente.')->with('download_url', $url);
    }
}
