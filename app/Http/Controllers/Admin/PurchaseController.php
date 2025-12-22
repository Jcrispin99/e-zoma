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
        // Simple dashboard for now
        return Inertia::render('purchases/Index');
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
}
