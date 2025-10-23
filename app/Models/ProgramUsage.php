<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramUsage extends Model
{
    protected $fillable = [
        'program_id',
        'customer_id',
        'reward_id',
        'order_type', // sale | pos
        'sale_id',
        'pos_order_id',
        'code',
        'discount_amount',
        'points_used',
        'channel', // sale | pos | ecommerce
        'used_at',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'points_used' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'program_id');
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(LoyaltyReward::class, 'reward_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
