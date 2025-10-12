<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PosConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_id',
        'warehouse_id',
        'receipt_sequence_id',
        'invoice_sequence_id',
        'default_customer_id',
        'is_active',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receiptSequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class, 'receipt_sequence_id');
    }

    public function invoiceSequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class, 'invoice_sequence_id');
    }

    public function defaultCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'default_customer_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }
}
