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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function image(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->images()->count() ? Storage::url($this->images()->first()->path) : asset('storage/images/images.png'),
        );
    }

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
