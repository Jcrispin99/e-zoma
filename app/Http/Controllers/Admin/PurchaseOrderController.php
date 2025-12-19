<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Variant;
use Exception;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use App\Services\SequenceService;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_purchase-orders', PurchaseOrder::class);
        return view('admin.purchases-orders.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_purchase-orders', PurchaseOrder::class);
        return view('admin.purchases-orders.create');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('update_purchase-orders', $purchaseOrder);
        return view('admin.purchases-orders.edit', compact('purchaseOrder'));
    }

    /**
     * Generate a PDF for the specified resource.
     */
    public function pdf(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('export_pdf_purchase-orders', $purchaseOrder);
        $pdf = Pdf::loadView('pdf.purchase-order.show', [
            'po' => $purchaseOrder,
            'useLayout' => false,
            'isPublic' => false,
        ]);
        return $pdf->download('comprobante-compra-' . $purchaseOrder->id . '.pdf');
    }

    /**
     * Show a styled HTML preview of the Purchase Order PDF with a download button.
     */
    public function pdfView(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('export_pdf_purchase-orders', $purchaseOrder);
        return view('pdf.purchase-order.show', [
            'po' => $purchaseOrder,
            'useLayout' => true,
            'isPublic' => false,
        ]);
    }

    /**
     * Public HTML preview of the Purchase Order PDF (signed URL required).
     */
    public function publicPdfView(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('export_pdf_purchase-orders', $purchaseOrder);
        // Vista pública mínima sin layout admin (URL firmada)
        return view('pdf.purchase-order.show', [
            'po' => $purchaseOrder,
            'useLayout' => false,
            'isPublic' => true,
        ]);
    }

    public function indexWeb(Request $request)
    {
        Gate::authorize('read_purchase-orders', PurchaseOrder::class);

        $query = PurchaseOrder::with(['supplier']);

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

        $purchaseOrders = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('purchases/orders/Index', [
            'purchaseOrders' => $purchaseOrders,
            'filters' => $request->only(['search'])
        ]);
    }

    public function createWeb()
    {
        Gate::authorize('create_purchase-orders', PurchaseOrder::class);
        $suppliers = Supplier::all();
        $taxes = Tax::all();
        $journals = Journal::where('type', 'purchase')->get();
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

        return Inertia::render('purchases/orders/CreateEdit', [
            'suppliers' => $suppliers,
            'taxes' => $taxes,
            'journals' => $journals,
            'products' => $products,
        ]);
    }

    public function editWeb(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('update_purchase-orders', $purchaseOrder);
        $suppliers = Supplier::all();
        $taxes = Tax::all();
        $journals = Journal::where('type', 'purchase')->get();
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

        $purchaseOrder->load('supplier', 'variants.product', 'variants.attributeValues');

        return Inertia::render('purchases/orders/CreateEdit', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => $suppliers,
            'taxes' => $taxes,
            'journals' => $journals,
            'products' => $products,
        ]);
    }

    public function storeWeb(Request $request)
    {
        Gate::authorize('create_purchase-orders', PurchaseOrder::class);

        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'journal_id' => 'nullable|exists:journals,id',
            'serie' => 'nullable|string',
            'correlative' => 'nullable|string',
            'date' => 'nullable|date',
            'total' => 'nullable|numeric',
            'observation' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.id' => 'required|exists:variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric',
            'items.*.subtotal' => 'nullable|numeric',
        ]);

        $data['company_id'] = 1;
        $data['status'] = 'draft';

        if (!empty($data['journal_id'])) {
            try {
                $parts = SequenceService::getNextParts($data['journal_id']);
                $data['serie'] = $parts['serie'];
                $data['correlative'] = $parts['correlative'];
            } catch (Exception $e) {
                $data['serie'] = 'PO';
                $data['correlative'] = str_pad((string) rand(1, 999999), 8, '0', STR_PAD_LEFT);
            }
        }

        if (empty($data['serie'])) {
            $data['serie'] = 'PO';
        }
        if (empty($data['correlative'])) {
            $data['correlative'] = str_pad((string) rand(1, 999999), 8, '0', STR_PAD_LEFT);
        }

        $purchaseOrder = PurchaseOrder::create($data);

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
            $purchaseOrder->variants()->sync($syncData);
        }

        return redirect()->route('purchases.orders.index')->with('success', 'Orden de compra creada con éxito');
    }

    public function updateWeb(Request $request, PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('update_purchase-orders', $purchaseOrder);

        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'journal_id' => 'nullable|exists:journals,id',
            'serie' => 'nullable|string',
            'correlative' => 'nullable|string',
            'date' => 'nullable|date',
            'total' => 'nullable|numeric',
            'observation' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.id' => 'required|exists:variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric',
            'items.*.subtotal' => 'nullable|numeric',
        ]);

        $purchaseOrder->update($data);

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
            $purchaseOrder->variants()->sync($syncData);
        }

        return redirect()->back()->with('success', 'Orden de compra actualizada con éxito');
    }

    public function destroyWeb(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('delete_purchase-orders', $purchaseOrder);
        $purchaseOrder->delete();
        return redirect()->route('purchases.orders.index')->with('success', 'Orden de compra eliminada correctamente');
    }

    public function massDestroyWeb(Request $request)
    {
        Gate::authorize('delete_purchase-orders', PurchaseOrder::class);
        $ids = $request->input('ids');
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron registros.');
        }

        PurchaseOrder::whereIn('id', $ids)->delete();
        return redirect()->route('purchases.orders.index')->with('success', 'Ordenes de compra eliminadas correctamente');
    }

    public function confirmWeb(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('update_purchase-orders', $purchaseOrder);

        $purchaseOrder->update(['status' => 'confirmed']);

        return redirect()->back()->with('success', 'Orden de compra confirmada correctamente');
    }

    public function cancelWeb(PurchaseOrder $purchaseOrder)
    {
        // Gate::authorize('update', $purchaseOrder);

        if ($purchaseOrder->status === 'cancelled') {
            return back()->with(['error' => 'La orden ya está cancelada']);
        }

        // TODO: Revertir lógica de inventario si se había confirmado y sumado stock

        $purchaseOrder->update(['status' => 'cancelled']);

        return back()->with(['success' => 'Orden de compra cancelada correctamente']);
    }

    public function apiDetails(PurchaseOrder $purchaseOrder)
    {
        return response()->json($purchaseOrder->load(['variants.product', 'variants.attributeValues', 'supplier']));
    }
}
