<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Journal;
use App\Models\Tax;
use App\Models\Variant;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_purchases', Purchase::class);
        return view('admin.purchases.index');
    }

    public function dashboard()
    {
        $stats = [
            'purchases_count' => Purchase::count(),
            'orders_count' => PurchaseOrder::count(),
            'suppliers_count' => Supplier::count(),
            'products_count' => Variant::count(),
            'total_spent' => Purchase::sum('total'),
        ];

        $recentPurchases = Purchase::with('supplier')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'supplier_name' => $purchase->supplier->name,
                    'total' => $purchase->total,
                    'date' => $purchase->created_at->format('d/m/Y'),
                    'status' => $purchase->status
                ];
            });

        $monthlySpend = Purchase::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total) as total')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $topSuppliers = Purchase::selectRaw('supplier_id, SUM(total) as total_spent')
            ->with('supplier:id,name')
            ->groupBy('supplier_id')
            ->orderByDesc('total_spent')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->supplier->name,
                    'value' => $item->total_spent
                ];
            });

        return Inertia::render('purchases/Index', [
            'stats' => $stats,
            'recentPurchases' => $recentPurchases,
            'monthlySpend' => $monthlySpend,
            'topSuppliers' => $topSuppliers
        ]);
    }

    public function edit(Purchase $purchase)
    {
        Gate::authorize('update_purchases', $purchase);
        return view('admin.purchases.edit', compact('purchase'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_purchases', Purchase::class);
        return view('admin.purchases.create');
    }

    public function pdf(Purchase $purchase)
    {
        Gate::authorize('export_pdf_purchases', $purchase);
        $pdf = Pdf::loadView('pdf.purchase.show', [
            'purchase' => $purchase,
            'useLayout' => false,
            'isPublic' => false,
        ]);
        return $pdf->download('factura-' . $purchase->id . '.pdf');
    }

    /**
     * Show a styled HTML preview of the Purchase PDF with a download button.
     */
    public function pdfView(Purchase $purchase)
    {
        Gate::authorize('export_pdf_purchases', $purchase);
        return view('pdf.purchase.show', [
            'purchase' => $purchase,
            'useLayout' => true,
            'isPublic' => false,
        ]);
    }

    /**
     * Public HTML preview of the Purchase PDF (signed URL required).
     */
    public function publicPdfView(Purchase $purchase)
    {
        return view('pdf.purchase.show', [
            'purchase' => $purchase,
            'useLayout' => false,
            'isPublic' => true,
        ]);
    }

    // --- Inertia / Vue Methods (New) ---

    public function indexWeb(Request $request)
    {
        // Gate::authorize('read_purchases', Purchase::class);

        $query = Purchase::with(['supplier', 'purchaseOrder']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('serie', 'like', "%{$search}%")
                    ->orWhere('correlative', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('document_number', 'like', "%{$search}%");
                    });
            });
        }

        $purchases = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('purchases/invoices/Index', [
            'purchases' => $purchases,
            'filters' => $request->only(['search'])
        ]);
    }

    public function createWeb()
    {
        // Gate::authorize('create_purchases', Purchase::class);
        $suppliers = Supplier::all();
        $taxes = Tax::all();
        $journals = Journal::where('type', 'purchase')->get();

        $purchaseOrders = PurchaseOrder::whereIn('status', ['draft', 'confirmed'])
            ->selectRaw("id, supplier_id, concat(serie, '-', correlative) as label")
            ->orderBy('id', 'desc')
            ->get();

        $products = Variant::with(['product', 'attributeValues.attribute'])
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($variant) {
                $name = $variant->product->name;
                if ($variant->attributeValues->isNotEmpty()) {
                    $name .= ' - ' . $variant->attributeValues->map(fn($av) => $av->value)->join(', ');
                }
                $variant->full_name = $name;
                return $variant;
            });

        return Inertia::render('purchases/invoices/CreateEdit', [
            'suppliers' => $suppliers,
            'taxes' => $taxes,
            'journals' => $journals,
            'products' => $products,
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function storeWeb(Request $request)
    {
        // Gate::authorize('create_purchases', Purchase::class);

        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'journal_id' => 'nullable|exists:journals,id',
            'serie' => 'nullable|string',
            'correlative' => 'nullable|string',
            'date' => 'nullable|date',
            'total' => 'nullable|numeric',
            'observation' => 'nullable|string',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'items' => 'nullable|array',
            'items.*.id' => 'required|exists:variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric',
            'items.*.subtotal' => 'nullable|numeric',
        ]);

        $data['company_id'] = 1;

        $data['warehouse_id'] = Warehouse::value('id') ?? 1;

        $data['status'] = 'draft';

        if (empty($data['serie']) && !empty($data['journal_id'])) {
            $journal = Journal::find($data['journal_id']);
            if ($journal) {
                $data['serie'] = $journal->serie;
            }
        }

        if (empty($data['serie'])) {
            $data['serie'] = 'F001';
        }

        $purchase = Purchase::create($data);

        if (!empty($data['purchase_order_id'])) {
            $po = PurchaseOrder::find($data['purchase_order_id']);
            if ($po) {
                $po->update([
                    'billing_status' => 'complete',
                    'billed_at' => now(),
                ]);
            }
        }

        if (!empty($data['items'])) {
            $syncData = [];
            foreach ($data['items'] as $item) {
                $syncData[$item['id']] = [
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'subtotal' => $item['subtotal'] ?? 0,
                ];
            }
            $purchase->variants()->sync($syncData);
        }

        return redirect()->route('purchases.invoices.index')->with('success', 'Compra creada con éxito');
    }

    public function editWeb(Purchase $purchase)
    {
        // Gate::authorize('update_purchases', $purchase);
        $suppliers = Supplier::all();
        $taxes = Tax::all();
        $journals = Journal::where('type', 'purchase')->get();
        $purchaseOrders = PurchaseOrder::where('status', 'confirmed')->get()
            ->map(function ($po) {
                return [
                    'id' => $po->id,
                    'label' => "{$po->serie}-{$po->correlative}",
                    'supplier_id' => $po->supplier_id,
                ];
            });

        $products = Variant::with(['product', 'attributeValues.attribute'])
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($variant) {
                $name = $variant->product->name;
                if ($variant->attributeValues->isNotEmpty()) {
                    $name .= ' - ' . $variant->attributeValues->map(fn($av) => $av->value)->join(', ');
                }
                $variant->full_name = $name;
                return $variant;
            });

        $purchase->load('supplier', 'variants.product', 'variants.attributeValues', 'purchaseOrder');

        return Inertia::render('purchases/invoices/CreateEdit', [
            'purchase' => $purchase,
            'suppliers' => $suppliers,
            'taxes' => $taxes,
            'journals' => $journals,
            'products' => $products,
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function updateWeb(Request $request, Purchase $purchase)
    {
        // Gate::authorize('update_purchases', $purchase);

        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'journal_id' => 'nullable|exists:journals,id',
            'serie' => 'nullable|string',
            'correlative' => 'nullable|string',
            'date' => 'nullable|date',
            'total' => 'nullable|numeric',
            'observation' => 'nullable|string',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'items' => 'nullable|array',
            'items.*.id' => 'required|exists:variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric',
            'items.*.subtotal' => 'nullable|numeric',
        ]);

        $purchase->update($data);

        if (isset($data['items'])) {
            $syncData = [];
            foreach ($data['items'] as $item) {
                $syncData[$item['id']] = [
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'subtotal' => $item['subtotal'] ?? 0,
                ];
            }
            $purchase->variants()->sync($syncData);
        }

        return redirect()->back()->with('success', 'Compra actualizada con éxito');
    }

    public function destroyWeb(Purchase $purchase)
    {
        // Gate::authorize('delete_purchases', $purchase);
        $purchase->delete();
        return redirect()->route('purchases.invoices.index')->with('success', 'Compra eliminada correctamente');
    }

    public function massDestroyWeb(Request $request)
    {
        // Gate::authorize('delete_purchases', Purchase::class);
        $ids = $request->input('ids');
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron registros.');
        }

        Purchase::whereIn('id', $ids)->delete();
        return redirect()->route('purchases.invoices.index')->with('success', 'Compras eliminadas correctamente');
    }
    public function confirmWeb(Purchase $purchase)
    {
        // Gate::authorize('update_purchases', $purchase);
        if ($purchase->status !== 'draft') {
            return redirect()->back()->with('error', 'Solo compras en borrador pueden contabilizarse.');
        }

        $purchase->load(['variants']);
        $warehouseId = $purchase->warehouse_id;
        foreach ($purchase->variants as $variant) {
            \App\Facades\Kardex::registerEntry(
                $purchase,
                [
                    'id' => $variant->id,
                    'quantity' => (float) ($variant->pivot->quantity ?? 0),
                    'price' => (float) ($variant->pivot->price ?? 0),
                    'subtotal' => (float) ($variant->pivot->subtotal ?? 0),
                ],
                $warehouseId,
                'Compra'
            );
        }

        $purchase->update(['status' => 'posted']);

        if ($purchase->purchase_order_id && $purchase->purchaseOrder) {
            $this->recalcPoMetrics($purchase->purchaseOrder);
        }

        return redirect()->back()->with('success', 'Compra contabilizada correctamente.');
    }

    public function cancelWeb(Purchase $purchase)
    {
        // Gate::authorize('update_purchases', $purchase);
        if (in_array($purchase->payment_status, ['partial', 'paid'])) {
            return redirect()->back()->with('error', 'No permitido. La compra tiene pagos registrados. Anule los pagos antes de cancelar.');
        }

        if ($purchase->status === 'posted') {
            $purchase->load(['variants']);
            foreach ($purchase->variants as $variant) {
                \App\Facades\Kardex::registerExit(
                    $purchase,
                    [
                        'id' => $variant->id,
                        'quantity' => (float) ($variant->pivot->quantity ?? 0),
                        'price' => (float) ($variant->pivot->price ?? 0),
                        'subtotal' => (float) ($variant->pivot->subtotal ?? 0),
                    ],
                    $purchase->warehouse_id,
                    'Anulación de compra'
                );
            }
        }

        $purchase->update(['status' => 'cancelled']);

        if ($purchase->purchase_order_id && $purchase->purchaseOrder) {
            $this->recalcPoMetrics($purchase->purchaseOrder);
        }

        return redirect()->back()->with('success', 'Compra anulada correctamente.');
    }

    public function reopenWeb(Purchase $purchase)
    {
        // Gate::authorize('update_purchases', $purchase);
        if (!in_array($purchase->status, ['posted', 'cancelled'])) {
            return redirect()->back()->with('error', 'Estado no válido para reabrir.');
        }

        $purchase->update(['status' => 'draft']);

        if ($purchase->purchase_order_id && $purchase->purchaseOrder) {
            $this->recalcPoMetrics($purchase->purchaseOrder);
        }

        return redirect()->back()->with('success', 'Compra reabierta a borrador.');
    }

    public function markPaidWeb(Purchase $purchase)
    {
        // Gate::authorize('update_purchases', $purchase);
        if ($purchase->status !== 'posted') {
            return redirect()->back()->with('error', 'Solo se puede registrar pago para compras publicadas.');
        }
        if ($purchase->payment_status === 'paid') {
            return redirect()->back()->with('info', 'La compra ya está marcada como pagada.');
        }

        $purchase->update(['payment_status' => 'paid']);

        return redirect()->back()->with('success', 'Pago registrado correctamente.');
    }

    public function markUnpaidWeb(Purchase $purchase)
    {
        // Gate::authorize('update_purchases', $purchase);
        if ($purchase->status !== 'posted') {
            return redirect()->back()->with('error', 'Solo se puede anular el pago en compras publicadas.');
        }
        if ($purchase->payment_status === 'unpaid') {
            return redirect()->back()->with('info', 'La compra ya está como no pagada.');
        }

        $purchase->update(['payment_status' => 'unpaid']);

        return redirect()->back()->with('success', 'Pago anulado correctamente.');
    }

    private function recalcPoMetrics(PurchaseOrder $po): void
    {
        $orderedQty = DB::table('variantables')
            ->where('variantable_type', PurchaseOrder::class)
            ->where('variantable_id', $po->id)
            ->sum('quantity');

        $billedQty = DB::table('variantables')
            ->join('purchases', 'variantables.variantable_id', '=', 'purchases.id')
            ->where('variantables.variantable_type', Purchase::class)
            ->where('purchases.purchase_order_id', $po->id)
            ->where('purchases.status', '<>', 'cancelled')
            ->sum('variantables.quantity');

        $purchasesCount = Purchase::query()
            ->where('purchase_order_id', $po->id)
            ->where('status', '<>', 'cancelled')
            ->count();

        $billingStatus = $billedQty <= 0
            ? 'none'
            : ($billedQty < $orderedQty ? 'partial' : 'complete');

        $po->update([
            'ordered_qty_total' => (float) $orderedQty,
            'billed_qty_total' => (float) $billedQty,
            'purchases_count' => $purchasesCount,
            'billing_status' => $billingStatus,
            'billed_at' => $billingStatus === 'complete' ? now() : null,
        ]);
    }
}
