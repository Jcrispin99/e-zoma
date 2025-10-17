<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Journal;
use App\Models\PosOrder;
use App\Models\PosOrderLine;
use App\Models\PosPayment;
use App\Models\PosSession;
use App\Models\Sale;
use App\Models\Variant;
use App\Models\PosConfig;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PosSessionController extends Controller
{
    public function setOpeningBalance(Request $request, int $id)
    {
        /** @var PosSession $session */
        $session = PosSession::query()->findOrFail($id);

        if ($session->status !== 'open') {
            return response()->json(['message' => 'La sesión no está abierta'], 422);
        }

        $validated = $request->validate([
            'opening_balance' => 'required|numeric',
        ]);

        $session->opening_balance = $validated['opening_balance'];
        $session->save();

        return response()->json([
            'id' => $session->id,
            'status' => $session->status,
            'opened_at' => $session->opened_at,
            'opening_balance' => $session->opening_balance,
        ]);
    }

    public function open(Request $request)
    {
        $validated = $request->validate([
            'pos_config_id' => 'required|exists:pos_configs,id',
            'opening_balance' => 'nullable|numeric',
        ]);

        $userId = optional($request->user())->id ?? $request->integer('user_id');
        if (!$userId) {
            return response()->json(['message' => 'Usuario no autenticado o no provisto'], 401);
        }

        /** @var PosConfig $posConfig */
        $posConfig = PosConfig::query()->findOrFail($validated['pos_config_id']);

        // Si ya existe una sesión abierta para esta caja, continuar en esa
        $existing = PosSession::query()
            ->where('pos_config_id', $posConfig->id)
            ->where('status', 'open')
            ->whereNull('closed_at')
            ->orderByDesc('opened_at')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Ya existe una sesión abierta para esta caja',
                'id' => $existing->id,
                'status' => $existing->status,
                'opened_at' => $existing->opened_at,
                'opening_balance' => $existing->opening_balance,
                'pos_config' => [
                    'id' => $posConfig->id,
                    'name' => $posConfig->name,
                ],
                'redirect_url' => url('/pos/' . $existing->id),
            ], 409);
        }

        $session = PosSession::create([
            'pos_config_id' => $posConfig->id,
            'user_id' => $userId,
            'status' => 'open',
            'opening_balance' => $validated['opening_balance'] ?? 0,
            'opened_at' => Carbon::now(),
        ]);

        return response()->json([
            'id' => $session->id,
            'status' => $session->status,
            'opened_at' => $session->opened_at,
            'opening_balance' => $session->opening_balance,
            'pos_config' => [
                'id' => $posConfig->id,
                'name' => $posConfig->name,
            ],
            'redirect_url' => url('/pos/' . $session->id),
        ]);
    }

    public function bootstrap(Request $request, int $id)
    {
        /** @var PosSession $session */
        $session = PosSession::query()->with(['posConfig'])->findOrFail($id);

        $posConfig = $session->posConfig;

        // Usar journals configurados directamente
        $receiptJournalId = $posConfig->receipt_journal_id;
        $invoiceJournalId = $posConfig->invoice_journal_id;

        $receiptJournal = $receiptJournalId ? Journal::query()->find($receiptJournalId) : null;
        $invoiceJournal = $invoiceJournalId ? Journal::query()->find($invoiceJournalId) : null;

        $defaultCustomer = $posConfig->defaultCustomer ?? ($posConfig->default_customer_id ? Customer::query()->find($posConfig->default_customer_id) : null);

        $categories = Category::query()->select(['id', 'name'])->orderBy('name')->get();

        // Cargar variantes iniciales para el POS (primeras 24)
        // Ajuste: usar relación morfológica de imágenes y el accessor de imagen
        // para evitar columnas inexistentes como variant_id/url en la tabla images.
        $variants = Variant::query()
            ->with(['product:id,name', 'images'])
            ->select(['id', 'sku', 'product_id', 'price'])
            ->limit(24)
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'name' => $v->product->name ?? null,
                    'price' => $v->price,
                    'image' => $v->image, // accessor en Variant
                ];
            });

        return response()->json([
            'session' => [
                'id' => $session->id,
                'status' => $session->status,
                'opened_at' => $session->opened_at,
                'opening_balance' => $session->opening_balance,
            ],
            'config' => [
                'id' => $posConfig->id,
                'name' => $posConfig->name,
                'company_id' => $posConfig->company_id,
                'warehouse_id' => $posConfig->warehouse_id,
                'default_customer_id' => $posConfig->default_customer_id,
            ],
            'sequences' => [
                'receipt' => $receiptJournal ? [
                    'sequence_id' => $receiptJournal->sequence_id,
                    'journal_id' => $receiptJournal->id,
                    'serie_code' => $receiptJournal->code,
                ] : null,
                'invoice' => $invoiceJournal ? [
                    'sequence_id' => $invoiceJournal->sequence_id,
                    'journal_id' => $invoiceJournal->id,
                    'serie_code' => $invoiceJournal->code,
                ] : null,
            ],
            'default_customer' => $defaultCustomer ? [
                'id' => $defaultCustomer->id,
                'name' => $defaultCustomer->name,
                'document_number' => $defaultCustomer->document_number,
            ] : null,
            'categories' => $categories,
            'variants' => $variants,
        ]);
    }

    public function sync(Request $request, int $id, SequenceService $sequenceService)
    {
        /** @var PosSession $session */
        $session = PosSession::query()->with(['posConfig'])->findOrFail($id);
        $posConfig = $session->posConfig;

        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.customer_id' => 'required|exists:customers,id',
            'orders.*.total_amount' => 'required|numeric',
            'orders.*.voucher_type' => 'required|in:receipt,invoice',
            'orders.*.lines' => 'required|array|min:1',
            'orders.*.lines.*.variant_id' => 'required|exists:variants,id',
            'orders.*.lines.*.quantity' => 'required|numeric|min:0.0001',
            'orders.*.lines.*.price' => 'required|numeric',
            'orders.*.lines.*.subtotal' => 'required|numeric',
            'orders.*.payments' => 'array',
            'orders.*.payments.*.payment_method_id' => 'required|exists:payment_methods,id',
            'orders.*.payments.*.amount' => 'required|numeric|min:0',
        ]);

        $created = [];

        DB::beginTransaction();
        try {
            foreach ($validated['orders'] as $order) {
                $posOrder = PosOrder::create([
                    'pos_session_id' => $session->id,
                    'customer_id' => $order['customer_id'],
                    'total_amount' => $order['total_amount'],
                    'status' => 'synced',
                ]);

                // Guardar líneas del pedido POS
                foreach ($order['lines'] as $line) {
                    PosOrderLine::create([
                        'pos_order_id' => $posOrder->id,
                        'variant_id' => $line['variant_id'],
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'subtotal' => $line['subtotal'],
                    ]);
                }

                // Guardar pagos del pedido POS
                foreach (($order['payments'] ?? []) as $payment) {
                    PosPayment::create([
                        'pos_order_id' => $posOrder->id,
                        'payment_method_id' => $payment['payment_method_id'],
                        'amount' => $payment['amount'],
                    ]);
                }

                // Generar Venta con correlativo según voucher_type
                $journal = null;
                if ($order['voucher_type'] === 'receipt' && $posConfig->receipt_journal_id) {
                    $journal = Journal::query()->find($posConfig->receipt_journal_id);
                } elseif ($order['voucher_type'] === 'invoice' && $posConfig->invoice_journal_id) {
                    $journal = Journal::query()->find($posConfig->invoice_journal_id);
                }

                if (!$journal) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Diario no configurado para el tipo de documento (receipt/invoice).',
                    ], 422);
                }

                $serie = null;
                $correlative = null;
                if ($journal) {
                    $parts = $sequenceService->getNextParts($journal->id);
                    $serie = $parts['serie'] ?? null;
                    $correlative = $parts['correlative'] ?? null;
                }

                // Crear Sale
                /** @var Sale $sale */
                $sale = new Sale();
                // Sales.voucher_type es entero: 1=receipt, 2=invoice
                $sale->voucher_type = $order['voucher_type'] === 'invoice' ? 2 : 1;
                $sale->serie = $serie;
                $sale->correlative = $correlative;
                $sale->date = now();
                $sale->customer_id = $order['customer_id'];
                $sale->warehouse_id = $posConfig->warehouse_id;
                $sale->company_id = $posConfig->company_id;
                $sale->total = $order['total_amount'];
                $sale->pos_order_id = $posOrder->id;
                $sale->journal_id = $journal->id; // Asegurar FK al diario
                $sale->save();

                // Asociar variantes a la venta (pivot con quantity y price)
                foreach ($order['lines'] as $line) {
                    $sale->variants()->attach($line['variant_id'], [
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'subtotal' => $line['subtotal'],
                    ]);
                }

                // Registrar movimiento de inventario (salida) por venta en el almacén de la config POS
                foreach ($order['lines'] as $line) {
                    $prev = Inventory::query()
                        ->where('variant_id', $line['variant_id'])
                        ->where('warehouse_id', $posConfig->warehouse_id)
                        ->orderByDesc('id')
                        ->first();

                    $prevQty = $prev?->quantity_balance ?? 0;
                    $newBalance = $prevQty - abs($line['quantity']);

                    Inventory::query()->create([
                        'variant_id' => $line['variant_id'],
                        'warehouse_id' => $posConfig->warehouse_id,
                        'quantity_in' => 0,
                        'quantity_out' => abs($line['quantity']),
                        'quantity_balance' => $newBalance,
                        'inventoryable_id' => $sale->id,
                        'inventoryable_type' => Sale::class,
                    ]);
                }

                $created[] = [
                    'pos_order_id' => $posOrder->id,
                    'sale_id' => $sale->id,
                    'serie' => $sale->serie,
                    'correlative' => $sale->correlative,
                ];
            }

            DB::commit();

            return response()->json([
                'status' => 'ok',
                'synced' => $created,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function close(Request $request, int $id)
    {
        /** @var PosSession $session */
        $session = PosSession::query()->with(['orders.payments'])->findOrFail($id);

        $validated = $request->validate([
            'closing_balance' => 'required|numeric',
        ]);

        $theoretical = $session->orders->flatMap->payments->sum('amount');
        $difference = ($validated['closing_balance'] ?? 0) - $theoretical;

        $session->closing_balance = $validated['closing_balance'];
        $session->closed_at = Carbon::now();
        $session->status = 'closed';
        $session->save();

        return response()->json([
            'id' => $session->id,
            'status' => $session->status,
            'closed_at' => $session->closed_at,
            'closing_balance' => $session->closing_balance,
            'theoretical_cash' => $theoretical,
            'difference' => $difference,
        ]);
    }

    public function summary(Request $request, int $id)
    {
        /** @var PosSession $session */
        $session = PosSession::query()->with(['orders.payments'])->findOrFail($id);

        $ordersCount = $session->orders()->count();
        $totalAmount = $session->orders()->sum('total_amount');
        $paymentsTotal = $session->orders->flatMap->payments->sum('amount');

        return response()->json([
            'id' => $session->id,
            'status' => $session->status,
            'opened_at' => $session->opened_at,
            'closed_at' => $session->closed_at,
            'opening_balance' => $session->opening_balance,
            'closing_balance' => $session->closing_balance,
            'orders_count' => $ordersCount,
            'orders_total_amount' => $totalAmount,
            'payments_total_amount' => $paymentsTotal,
        ]);
    }
}
