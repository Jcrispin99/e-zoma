<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    public function posPayments(): HasMany
    {
        return $this->hasMany(PosPayment::class);
    }
}
