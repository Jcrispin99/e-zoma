<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\Variant;
use App\Models\Tax;
use App\Models\Journal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\SequenceService;
use Exception;

class SaleController extends Controller
{
    public function dashboard()
    {
        Gate::authorize('read_sales', Sale::class);

        $stats = [
            'sales_count' => Sale::count(),
            'orders_count' => Quote::count(),
            'customers_count' => Customer::count(),
            'products_count' => Variant::count(),
            'total_sales' => Sale::sum('total'),
        ];

        $recentSales = Sale::with('customer')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'customer_name' => $sale->customer->name ?? 'Cliente General',
                    'total' => $sale->total,
                    'date' => $sale->created_at->format('d/m/Y'),
                    'status' => $sale->status
                ];
            });

        $monthlySales = Sale::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total) as total')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $topCustomers = Sale::selectRaw('customer_id, SUM(total) as total_spent')
            ->with('customer:id,name')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->customer->name ?? 'Cliente General',
                    'value' => $item->total_spent
                ];
            });

        return Inertia::render('sales/Index', [
            'stats' => $stats,
            'recentSales' => $recentSales,
            'monthlySales' => $monthlySales,
            'topCustomers' => $topCustomers
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_sales', Sale::class);
        return view('admin.sales.index');
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_sales', Sale::class);
        return view('admin.sales.create');
    }

    public function edit(Sale $sale)
    {
        Gate::authorize('update_sales', $sale);
        return view('admin.sales.edit', compact('sale'));
    }



    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(Sale $sale)
    {
        Gate::authorize('export_pdf_sales', $sale);
        $pdf = Pdf::loadView('pdf.sale.show', [
            'sale' => $sale,
            'useLayout' => false,
            'isPublic' => false,
        ]);
        return $pdf->download('comprobante-venta-' . $sale->id . '.pdf');
    }

    /**
     * Show a styled HTML preview of the Sale PDF with a download button.
     */
    public function pdfView(Sale $sale)
    {
        Gate::authorize('export_pdf_sales', $sale);
        return view('pdf.sale.show', [
            'sale' => $sale,
            'useLayout' => true,
            'isPublic' => false,
        ]);
    }

    /**
     * Public HTML preview of the Sale PDF (signed URL required).
     */
    public function publicPdfView(Sale $sale)
    {
        Gate::authorize('export_pdf_sales', $sale);
        return view('pdf.sale.show', [
            'sale' => $sale,
            'useLayout' => false,
            'isPublic' => true,
        ]);
    }
}
