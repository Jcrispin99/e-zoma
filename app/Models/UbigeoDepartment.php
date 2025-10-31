<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UbigeoDepartment extends Model
{
    protected $table = 'ubigeo_departments';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function provinces(): HasMany
    {
        return $this->hasMany(UbigeoProvince::class, 'department_id', 'id');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(UbigeoDistrict::class, 'department_id', 'id');
    }
}
