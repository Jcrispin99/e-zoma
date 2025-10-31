<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;
    protected $fillable = [
        'name'
    ];

    public function attributeValues(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    // Scope to search attributes
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', '%' . $search . '%');
    }

    // Get or create attribute
    public static function getOrCreate($name)
    {
        return static::firstOrCreate([
            'name' => trim($name)
        ]);
    }

    // Get attribute with values
    public function scopeWithValues($query)
    {
        return $query->with('attributeValues');
    }
}
