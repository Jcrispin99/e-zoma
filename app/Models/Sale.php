<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendSunatInvoice;

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

    public function reason()
    {
        return $this->belongsTo(Reason::class);
    }

    public function variants()
    {
        return $this->morphToMany(Variant::class, 'variantable')
            ->withPivot('quantity', 'price', 'subtotal')
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
                // Marcar como en cola para SUNAT apenas se crea
                $sale->sunat_status = $sale->sunat_status ?: 'queued';
                $sale->save();
                Log::info('Dispatching SendSunatInvoice job', [
                    'sale_id' => $sale->id,
                    'pos_order_id' => $sale->pos_order_id,
                    'journal_id' => $sale->journal_id,
                ]);
                // Ejecutar el job tras el commit de la transacción
                SendSunatInvoice::dispatch($sale->id)->afterCommit();
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch SendSunatInvoice job', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
