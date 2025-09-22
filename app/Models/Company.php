<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'comercial_name',
        'legal_name',
        'vat',
        'address',
    ];

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function parent()
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    public function isSubsidiary()
    {
        return !is_null($this->parent_id);
    }
}
