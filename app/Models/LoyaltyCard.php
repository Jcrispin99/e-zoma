<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyCard extends Model
{
    protected $fillable = [
        'program_id',
        'company_id',
        'customer_id',
        'code',
        'expiration_date',
        'points',
        'sale_id',
        'source_pos_order_id',
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'points' => 'decimal:2',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'program_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function sourcePosOrder(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class, 'source_pos_order_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(LoyaltyCardMovement::class, 'card_id');
    }
}
