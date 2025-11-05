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
        'receipt_journal_id',
        'invoice_journal_id',
        'default_customer_id',
        'default_tax_id',
        'is_active',
        // impuestos IGV
        'apply_tax',
        'tax_rate',
        'prices_include_tax',
    ];

    protected $casts = [
        'apply_tax' => 'bool',
        'tax_rate' => 'float',
        'prices_include_tax' => 'bool',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receiptJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'receipt_journal_id');
    }

    public function invoiceJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'invoice_journal_id');
    }

    public function defaultCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'default_customer_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    public function defaultTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'default_tax_id');
    }
}
