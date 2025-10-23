<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyCardMovement extends Model
{
    protected $fillable = [
        'card_id',
        'type', // credit | debit
        'points',
        'sale_id',
        'pos_order_id',
        'reason',
    ];

    protected $casts = [
        'points' => 'decimal:2',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCard::class, 'card_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function posOrder(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class);
    }
}
