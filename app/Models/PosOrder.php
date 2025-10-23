<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PosOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_session_id',
        'customer_id',
        'total_amount',
        'status',
    ];

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosOrderLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class, 'pos_order_id');
    }
}
