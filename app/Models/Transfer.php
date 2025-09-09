<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $fillable = [
        'type',
        'serie',
        'correlative',
        'date',
        'total',
        'observation',
        'origin_warehouse_id',
        'desination_warehouse_id',
    ];
}
