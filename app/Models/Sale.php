<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendSunatInvoice;
use App\Models\SunatConnection;

class Sale extends Model
{
    protected $fillable = [
        'serie',
        'correlative',
        'journal_id',
        'date',
        'quote_id',
        'customer_id',
        'warehouse_id',
        'total',
        'observation',
        'company_id',
        'pos_order_id',
        'status',
        'payment_status',
        'original_sale_id',
        'reason_id',
        'original_document_type_code',
        'original_serie',
        'original_correlative',
        // SUNAT
        'sunat_status',
        'sunat_response',
    ];

    protected $casts = [
        'date' => 'datetime',
        'sunat_response' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function originalSale()
    {
        return $this->belongsTo(Sale::class, 'original_sale_id');
    }

    // Documentos derivados (devoluciones/notas) que apuntan a esta venta
    public function derivedSales()
    {
        return $this->hasMany(Sale::class, 'original_sale_id');
    }

    public function reason()
    {
        return $this->belongsTo(Reason::class);
    }

    public function variants()
    {
        return $this->morphToMany(Variant::class, 'variantable')
            ->withPivot('quantity', 'price', 'tax_rate', 'subtotal')
            ->withTimestamps();
    }
    public function inventories()
    {
        return $this->morphMany(Inventory::class, 'inventoryable');
    }

    protected static function booted()
    {
        static::created(function (Sale $sale) {
            try {
                // Solo auto‑enviar si:
                // - La venta está publicada
                // - El diario es fiscal y tiene código de documento SUNAT (01/03/07/08)
                // - Existe conexión SUNAT con token no vacío
                $isPosted = (string) $sale->status === 'posted';
                $journal = $sale->journal; // relación puede ser null
                $docType = (string) optional($journal)->document_type_code;
                $isFiscal = (bool) optional($journal)->is_fiscal;
                $isSunatDoc = $isFiscal && in_array($docType, ['01', '03', '07', '08'], true);
                $hasToken = SunatConnection::query()
                    ->where('company_id', (int) ($sale->company_id ?? 0))
                    ->whereNotNull('token_ikoodev')
                    ->where('token_ikoodev', '!=', '')
                    ->exists();

                if ($isPosted && $isSunatDoc && $hasToken) {
                    // Marcar como en cola para SUNAT y despachar job
                    $sale->sunat_status = $sale->sunat_status ?: 'queued';
                    $sale->save();
                    Log::info('Dispatching SendSunatInvoice job', [
                        'sale_id' => $sale->id,
                        'pos_order_id' => $sale->pos_order_id,
                        'journal_id' => $sale->journal_id,
                    ]);
                    SendSunatInvoice::dispatch($sale->id)->afterCommit();
                } elseif ($isPosted) {
                    // No auto‑envío: marcar estado según caso
                    if ($isSunatDoc && ! $hasToken) {
                        // Fiscal sin token: pendiente para envío manual cuando haya conexión
                        $sale->sunat_status = $sale->sunat_status ?: 'pending';
                    } else {
                        // No fiscal (Nota de Venta): marcado como omitido
                        $sale->sunat_status = $sale->sunat_status ?: 'skipped';
                    }
                    $sale->save();
                    Log::info('SUNAT auto-send skipped', [
                        'sale_id' => $sale->id,
                        'status' => $sale->status,
                        'journal_id' => $sale->journal_id,
                        'docType' => $docType,
                        'isFiscal' => $isFiscal,
                        'hasToken' => $hasToken,
                        'sunat_status' => $sale->sunat_status,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch SendSunatInvoice job', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
