<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LoyaltyRule extends Model
{
    protected $fillable = [
        'program_id',
        'product_category_id',
        'product_tag_id',
        'product_domain',
        'minimum_qty',
        'minimum_amount',
        'minimum_amount_tax_mode',
        'mode',
        'code',
        'promo_barcode',
        'reward_point_mode',
        'amount_per_point',
        'points_per_order',
        'active',
        'reward_point_split',
    ];

    protected $casts = [
        'active' => 'bool',
        'minimum_qty' => 'int',
        'minimum_amount' => 'decimal:2',
        'reward_point_split' => 'bool',
        'points_per_order' => 'int',
        'amount_per_point' => 'decimal:2',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'program_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }

    public function variants(): BelongsToMany
    {
        // Pivot opcional: loyalty_rule_variant
        return $this->belongsToMany(Variant::class, 'loyalty_rule_variant');
    }
}
