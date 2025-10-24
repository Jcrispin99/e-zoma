<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyaltyReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id', 'name', 'reward_type', 'points_cost', 'consume_all_points', 'discount_method', 'discount_scope', 'discount_category_id', 'reward_product_id', 'discount_percent', 'soles_per_point', 'fixed_amount', 'max_discount_amount', 'description', 'is_active', 'priority'
    ];

    public function program()
    {
        return $this->belongsTo(LoyaltyProgram::class, 'program_id');
    }

    public function rewardProduct()
    {
        return $this->belongsTo(Product::class, 'reward_product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'discount_category_id');
    }

    public function variants()
    {
        return $this->belongsToMany(Variant::class, 'loyalty_reward_variant', 'reward_id', 'variant_id');
    }
}
