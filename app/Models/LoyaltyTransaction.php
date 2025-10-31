<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id', 'type', 'points', 'available_points', 'reference_type', 'reference_id', 'idempotency_key', 'occurred_at', 'expires_at', 'notes'
    ];

    protected $dates = [
        'occurred_at', 'expires_at'
    ];

    public function account()
    {
        return $this->belongsTo(LoyaltyAccount::class, 'account_id');
    }
}
