<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sequence extends Model
{
    protected $fillable = [
        'name',
        'prefix',
        'sequence_size',
        'step',
        'next_number',
    ];

    public function posConfigsForReceipts(): HasMany
    {
        return $this->hasMany(PosConfig::class, 'receipt_sequence_id');
    }

    public function posConfigsForInvoices(): HasMany
    {
        return $this->hasMany(PosConfig::class, 'invoice_sequence_id');
    }
}
