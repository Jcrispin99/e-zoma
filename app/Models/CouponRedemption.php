<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponRedemption extends Model
{
    protected $fillable = [
        'card_id',
        'code',
        'sale_id',
        'pos_order_id',
        'channel', // sale | pos | ecommerce
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
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
