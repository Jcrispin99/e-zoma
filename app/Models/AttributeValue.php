<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttributeValue extends Model
{
    protected $fillable = [
        'attribute_id',
        'value'
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(Variant::class, 'attribute_value_variant');
    }

    // Scope to get values by attribute
    public function scopeByAttribute($query, $attributeId)
    {
        return $query->where('attribute_id', $attributeId);
    }

    // Scope to search values
    public function scopeSearch($query, $search)
    {
        return $query->where('value', 'like', '%' . $search . '%');
    }

    // Get or create attribute value
    public static function getOrCreate($attributeId, $value)
    {
        return static::firstOrCreate([
            'attribute_id' => $attributeId,
            'value' => trim($value)
        ]);
    }
}
