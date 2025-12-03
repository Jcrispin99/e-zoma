<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',

    ];

    protected $appends = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function image(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->mainImage ? Storage::url($this->mainImage->path) : null,
        );
    }

    public function sku(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->variants->first()?->sku,
        );
    }

    public function barcode(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->variants->first()?->barcode,
        );
    }

    public function variants()
    {
        return $this->hasMany(Variant::class)->orderBy('is_principal', 'desc');
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function mainImage()
    {
        return $this->morphOne(Image::class, 'imageable')->oldestOfMany();
    }
}
