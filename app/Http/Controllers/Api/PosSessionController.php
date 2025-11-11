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
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyEarnRule;
use App\Models\LoyaltyReward;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Facades\Kardex;

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

    /**
     * Open a POS session.
     */
    public function open(Request $request)
    {
        $validated = $request->validate([
            'pos_config_id' => 'required|exists:pos_configs,id',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $posConfig = PosConfig::query()->findOrFail($validated['pos_config_id']);
        $userId = $request->user()->id;

        /** @var PosSession $session */
        $session = PosSession::query()->create([
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
        $session = PosSession::query()->with(['posConfig.company.identity'])->findOrFail($id);

        $posConfig = $session->posConfig;

        // Usar journals configurados directamente
        $receiptJournalId = $posConfig->receipt_journal_id;
        $invoiceJournalId = $posConfig->invoice_journal_id;

        $receiptJournal = $receiptJournalId ? Journal::query()->with('sequence')->find($receiptJournalId) : null;
        $invoiceJournal = $invoiceJournalId ? Journal::query()->with('sequence')->find($invoiceJournalId) : null;

        $defaultCustomer = $posConfig->defaultCustomer ?? ($posConfig->default_customer_id ? Customer::query()->find($posConfig->default_customer_id) : null);

        $categories = Category::query()->select(['id', 'name'])->orderBy('name')->get();

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
                    'image' => $v->image,
                ];
            });

        $company = $posConfig->company; // incluye identity si está cargado en with
        $seller = $session->user; // vendedor actual

        return response()->json([
            'session' => [
                'id' => $session->id,
                'status' => $session->status,
                'opened_at' => $session->opened_at,
                'opening_balance' => $session->opening_balance,
            ],
            'pos' => [
                'id' => $posConfig->id,
                'name' => $posConfig->name,
            ],
            'seller' => $seller ? [
                'id' => $seller->id,
                'name' => $seller->name,
                'email' => $seller->email,
            ] : null,
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name,
                'trade_name' => $company->trade_name,
                'document_number' => $company->document_number,
                'address' => $company->address,
                'city' => $company->city,
                'department' => $company->department,
                'district' => $company->district,
                'email' => $company->email,
                'phone' => $company->phone,
                'logo' => $company->logo ?? null,
                'slogan' => $company->slogan,
                'policies' => $company->policies,
                'identity' => [
                    'name' => optional($company->identity)->name,
                ],
            ] : null,
            'config' => [
                'id' => $posConfig->id,
                'name' => $posConfig->name,
                'company_id' => $posConfig->company_id,
                'warehouse_id' => $posConfig->warehouse_id,
                'default_customer_id' => $posConfig->default_customer_id,
                // IGV
                'apply_tax' => (bool) ($posConfig->apply_tax ?? true),
                'tax_rate' => (float) ($posConfig->tax_rate ?? 0.18),
                'prices_include_tax' => (bool) ($posConfig->prices_include_tax ?? false),
            ],
            'sequences' => [
                'receipt' => $receiptJournal ? [
                    'sequence_id' => $receiptJournal->sequence_id,
                    'journal_id' => $receiptJournal->id,
                    'serie_code' => $receiptJournal->code,
                    'journal_name' => $receiptJournal->name,
                    'preview_correlative' => $receiptJournal->sequence ? str_pad($receiptJournal->sequence->next_number, $receiptJournal->sequence->sequence_size, '0', STR_PAD_LEFT) : null,
                ] : null,
                'invoice' => $invoiceJournal ? [
                    'sequence_id' => $invoiceJournal->sequence_id,
                    'journal_id' => $invoiceJournal->id,
                    'serie_code' => $invoiceJournal->code,
                    'journal_name' => $invoiceJournal->name,
                    'preview_correlative' => $invoiceJournal->sequence ? str_pad($invoiceJournal->sequence->next_number, $invoiceJournal->sequence->sequence_size, '0', STR_PAD_LEFT) : null,
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
            // Lealtad opcional
            'orders.*.loyalty.points_spent' => 'nullable|integer|min:0',
            'orders.*.loyalty.discount_amount' => 'nullable|numeric|min:0',
            'orders.*.loyalty.points_earned' => 'nullable|numeric|min:0',
        ]);

        $synced = [];

        DB::beginTransaction();
        try {
            foreach ($validated['orders'] as $order) {
                $customerId = $order['customer_id'];
                $customer = Customer::query()->findOrFail($customerId);

                // Determinar journal según tipo de voucher
                $journalId = $order['voucher_type'] === 'invoice'
                    ? $posConfig->invoice_journal_id
                    : $posConfig->receipt_journal_id;

                // Numeración: serie y correlativo
                $parts = SequenceService::getNextParts((int) $journalId);
                $serie = $parts['serie'];
                $correlative = $parts['correlative'];

                // Crear orden POS primero
                $paidSum = 0;
                if (!empty($order['payments'])) {
                    foreach ($order['payments'] as $p) {
                        $paidSum += (float) $p['amount'];
                    }
                }
                $paymentStatus = $paidSum <= 0 ? 'unpaid' : ($paidSum + 0.00001 >= (float) $order['total_amount'] ? 'paid' : 'partial');

                /** @var PosOrder $posOrder */
                $posOrder = PosOrder::query()->create([
                    'pos_session_id' => $session->id,
                    'customer_id' => $customerId,
                    'total_amount' => $order['total_amount'],
                    'status' => $paymentStatus === 'paid' ? 'paid' : 'open',
                ]);

                // Crear líneas de la orden POS
                foreach ($order['lines'] as $line) {
                    PosOrderLine::query()->create([
                        'pos_order_id' => $posOrder->id,
                        'variant_id' => $line['variant_id'],
                        'price' => $line['price'],
                        'quantity' => $line['quantity'],
                        'subtotal' => $line['subtotal'],
                    ]);
                }

                // Registrar pagos
                if (!empty($order['payments'])) {
                    foreach ($order['payments'] as $p) {
                        PosPayment::query()->create([
                            'pos_order_id' => $posOrder->id,
                            'payment_method_id' => $p['payment_method_id'],
                            'amount' => $p['amount'],
                        ]);
                    }
                }

                // Crear Sale con numeración y vínculos requeridos
                $sale = Sale::query()->create([
                    'serie' => $serie,
                    'correlative' => $correlative,
                    'journal_id' => (int) $journalId,
                    'customer_id' => $customerId,
                    'warehouse_id' => (int) $posConfig->warehouse_id,
                    'total' => (float) $order['total_amount'],
                    'company_id' => (int) $posConfig->company_id,
                    'pos_order_id' => $posOrder->id,
                    'status' => 'posted',
                    'payment_status' => $paymentStatus,
                ]);

                // Adjuntar variantes a la venta para edición en Admin
                $syncData = [];
                $ratePct = 0.0;
                try {
                    $ratePct = (float) ((bool) ($posConfig->apply_tax ?? true)
                        ? round(((float) ($posConfig->tax_rate ?? 0.0)) * 100, 2)
                        : 0.0);
                } catch (\Throwable $e) {
                    $ratePct = 0.0;
                }
                foreach ($order['lines'] as $line) {
                    $syncData[$line['variant_id']] = [
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'subtotal' => $line['subtotal'],
                        'tax_rate' => $ratePct,
                    ];
                }
                $sale->variants()->sync($syncData);

                // Registrar movimientos de inventario (Kardex) por salida de venta
                foreach ($order['lines'] as $line) {
                    Kardex::registerExit(
                        $sale,
                        [
                            'id' => $line['variant_id'],
                            'quantity' => $line['quantity'],
                            'price' => $line['price'],
                        ],
                        (int) $posConfig->warehouse_id,
                        'Venta'
                    );
                }

                // Ajustar puntos de lealtad si aplica (crear cuenta si no existe)
                if (!empty($order['loyalty'])) {
                    $loy = $order['loyalty'];
                    $account = LoyaltyAccount::query()->firstOrCreate(
                        ['customer_id' => $customerId],
                        ['points_balance' => 0, 'points_lifetime' => 0, 'status' => 'active']
                    );

                    // Redeem
                    $pointsSpent = (int) ($loy['points_spent'] ?? 0);
                    if ($pointsSpent > 0 && $account->points_balance >= $pointsSpent) {
                        $account->points_balance -= $pointsSpent;
                        $account->save();
                        LoyaltyTransaction::query()->create([
                            'account_id' => $account->id,
                            'type' => 'redeem',
                            'points' => $pointsSpent,
                            'available_points' => null,
                            'reference_type' => 'pos_order',
                            'reference_id' => $posOrder->id,
                            'idempotency_key' => null,
                            'occurred_at' => Carbon::now(),
                            'expires_at' => null,
                            'notes' => 'POS redeem',
                        ]);
                    }

                    // Earn
                    $pointsEarned = round((float) ($loy['points_earned'] ?? 0), 2);
                    if ($pointsEarned > 0) {
                        $account->points_balance = round((float) $account->points_balance + $pointsEarned, 2);
                        $account->points_lifetime = round((float) $account->points_lifetime + $pointsEarned, 2);
                        $account->save();
                        LoyaltyTransaction::query()->create([
                            'account_id' => $account->id,
                            'type' => 'earn',
                            'points' => $pointsEarned,
                            'available_points' => $pointsEarned,
                            'reference_type' => 'pos_order',
                            'reference_id' => $posOrder->id,
                            'idempotency_key' => null,
                            'occurred_at' => Carbon::now(),
                            'expires_at' => null,
                            'notes' => 'POS earn',
                        ]);
                    }
                }

                $synced[] = [
                    'sale_id' => $sale->id,
                    'pos_order_id' => $posOrder->id,
                    'voucher_type' => $order['voucher_type'],
                    'customer_id' => $customerId,
                    'total_amount' => (float) $order['total_amount'],
                    'payment_status' => $paymentStatus,
                    'serie' => $serie,
                    'correlative' => $correlative,
                ];
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(['synced' => $synced]);
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

    public function close(Request $request, int $id)
    {
        /** @var PosSession $session */
        $session = PosSession::query()->with(['orders.payments'])->findOrFail($id);

        if ($session->status !== 'open') {
            return response()->json(['message' => 'La sesión no está abierta'], 422);
        }

        $validated = $request->validate([
            'closing_balance' => 'required|numeric|min:0',
        ]);

        $session->closing_balance = $validated['closing_balance'];
        $session->closed_at = Carbon::now();
        $session->status = 'closed';
        $session->save();

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
