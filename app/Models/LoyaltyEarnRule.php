<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyaltyEarnRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id', 'name', 'basis', 'points_per_sol', 'points_per_unit', 'points_per_order', 'min_qty', 'min_amount', 'scope_type', 'category_id', 'is_active', 'priority', 'description'
    ];

    public function program()
    {
        return $this->belongsTo(LoyaltyProgram::class, 'program_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'loyalty_earn_rule_product', 'earn_rule_id', 'product_id');
    }

    public function variants()
    {
        return $this->belongsToMany(Variant::class, 'loyalty_earn_rule_variant', 'earn_rule_id', 'variant_id');
    }
}
