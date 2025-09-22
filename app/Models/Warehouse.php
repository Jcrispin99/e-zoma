<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
}
