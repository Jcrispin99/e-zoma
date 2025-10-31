<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UbigeoProvince extends Model
{
    protected $table = 'ubigeo_provinces';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function department(): BelongsTo
    {
        return $this->belongsTo(UbigeoDepartment::class, 'department_id', 'id');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(UbigeoDistrict::class, 'province_id', 'id');
    }
}
