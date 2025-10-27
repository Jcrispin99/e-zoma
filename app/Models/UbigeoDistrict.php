<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UbigeoDistrict extends Model
{
    protected $table = 'ubigeo_districts';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function province(): BelongsTo
    {
        return $this->belongsTo(UbigeoProvince::class, 'province_id', 'id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(UbigeoDepartment::class, 'department_id', 'id');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'district_id', 'id');
    }
}