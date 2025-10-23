<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LoyaltyReward extends Model
{
    protected $fillable = [
        'program_id',
        'reward_type',
        'discount_mode',
        'discount_applicability',
        'discount',
        'discount_max_amount',
        'reward_product_id',
        'reward_product_qty',
        'required_points',
        'clear_wallet',
        'description',
        'active',
        // legacy compatibility
        'type',
        'name',
    ];

    protected $casts = [
        'active' => 'bool',
        'clear_wallet' => 'bool',
        'discount' => 'decimal:2',
        'discount_max_amount' => 'decimal:2',
        'required_points' => 'decimal:2',
    ];

    // Prefer reward_type, but fall back to legacy 'type' if present
    public function getRewardTypeAttribute($value)
    {
        return $value ?? ($this->attributes['type'] ?? null);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'program_id');
    }

    public function rewardProduct(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'reward_product_id');
    }

    public function variants(): BelongsToMany
    {
        // Pivot opcional: loyalty_reward_variant (productos afectados)
        return $this->belongsToMany(Variant::class, 'loyalty_reward_variant');
    }
}
