<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sequence extends Model
{
    protected $fillable = [
        'sequence_size',
        'step',
        'next_number',
    ];

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }
}
