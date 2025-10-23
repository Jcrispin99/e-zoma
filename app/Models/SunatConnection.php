<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SunatConnection extends Model
{
    protected $fillable = [
        'company_id',
        'token_apiperu',
        'token_ikoodev',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
