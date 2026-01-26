<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Variant;
use App\Models\Tax;
use App\Models\Journal;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Services\SequenceService;
use Exception;

class SaleOrderController extends Controller
{
    public function indexWeb(Request $request)
    {
        // Gate::authorize('read_quotes', Quote::class); // Adjust permission as needed

        $query = Quote::with(['customer']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('serie', 'like', "%{$search}%")
                    ->orWhere('correlative', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('document_number', 'like', "%{$search}%");
                    });
            });
        }

        $sales = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('sales/invoices/Index', [
            'sales' => $sales,
            'filters' => $request->only(['search'])
        ]);
    }

    public function createWeb()
    {
        // Gate::authorize('create_quotes', Quote::class);
        $customers = Customer::select('id', 'name', 'identity_id', 'document_number', 'address', 'email', 'phone')->with('identity')->get();
        $journals = Journal::where('type', 'quote')->get();
        $taxes = Tax::all();
        $products = Variant::with(['product', 'attributeValues'])->get()->map(function ($variant) {
            return $variant;
        });

        $quotes = Quote::where('status', 'posted')->with('customer')->get()->map(function ($q) {
            return [
                'id' => $q->id,
                'label' => ($q->serie . '-' . $q->correlative) . ' - ' . ($q->customer->name ?? ''),
                'customer_id' => $q->customer_id
            ];
        });

        return Inertia::render('sales/invoices/CreateEdit', [
            'customers' => $customers,
            'journals' => $journals,
            'taxes' => $taxes,
            'products' => $products,
            'quotes' => $quotes
        ]);
    }

    public function storeWeb(Request $request)
    {
        // Gate::authorize('create_quotes', Quote::class);

        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
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
                return redirect()->back()->with('error', 'Error al generar la secuencia: ' . $e->getMessage());
            }
        }

        if (empty($data['serie'])) {
            $data['serie'] = 'COT';
        }
        if (empty($data['correlative'])) {
            $data['correlative'] = str_pad((string) rand(1, 999999), 8, '0', STR_PAD_LEFT);
        }

        $sale = Quote::create($data);

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
            $sale->variants()->sync($syncData);
        }

        return redirect()->route('sales.orders.index')->with('success', 'Orden de venta creada con éxito');
    }

    public function editWeb(Quote $sale)
    {
        // Gate::authorize('update_quotes', $sale);
        $sale->load(['customer.identity', 'variants.product', 'variants.attributeValues']);
        $customers = Customer::select('id', 'name', 'identity_id', 'document_number', 'address', 'email', 'phone')->with('identity')->get();
        $journals = Journal::where('type', 'quote')->get();
        $taxes = Tax::all();
        $products = Variant::with(['product', 'attributeValues'])->get();

        return Inertia::render('sales/invoices/CreateEdit', [
            'sale' => $sale,
            'customers' => $customers,
            'journals' => $journals,
            'taxes' => $taxes,
            'products' => $products
        ]);
    }

    public function updateWeb(Request $request, Quote $sale)
    {
        // Gate::authorize('update_quotes', $sale);

        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
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

        $sale->update($data);

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
            $sale->variants()->sync($syncData);
        }

        return redirect()->route('sales.orders.index')->with('success', 'Orden de venta actualizada con éxito');
    }

    public function destroyWeb(Quote $sale)
    {
        // Gate::authorize('delete_quotes', $sale);
        $sale->delete();
        return redirect()->route('sales.orders.index')->with('success', 'Venta eliminada con éxito');
    }

    public function massDestroyWeb(Request $request)
    {
        // Gate::authorize('delete_quotes', Quote::class);
        $ids = $request->input('ids');
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron registros.');
        }
        Quote::whereIn('id', $ids)->delete();
        return redirect()->route('sales.orders.index')->with('success', 'Ventas eliminadas correctamente');
    }

    public function postWeb(Quote $sale)
    {
        // Gate::authorize('update_quotes', $sale);
        if ($sale->status !== 'draft') {
            return back()->with(['error' => 'Solo cotizaciones en borrador pueden publicarse.']);
        }
        $sale->update(['status' => 'posted']);
        return back()->with(['success' => 'Cotización publicada correctamente.']);
    }

    public function cancelWeb(Quote $sale)
    {
        // Gate::authorize('update_quotes', $sale);
        if ($sale->status === 'cancelled') {
            return back()->with(['error' => 'La cotización ya está cancelada.']);
        }
        $sale->update(['status' => 'cancelled']);
        return back()->with(['success' => 'Cotización cancelada correctamente.']);
    }

    public function reopenWeb(Quote $sale)
    {
        // Gate::authorize('update_quotes', $sale);
        if (!in_array($sale->status, ['posted', 'cancelled'])) {
            return back()->with(['error' => 'No se puede reabrir esta cotización.']);
        }
        $sale->update(['status' => 'draft']);
        return back()->with(['success' => 'Cotización reabierta a borrador.']);
    }
}
