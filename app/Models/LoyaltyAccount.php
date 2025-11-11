<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyaltyAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'points_balance', 'points_lifetime', 'status'
    ];

    protected $casts = [
        'points_balance' => 'float',
        'points_lifetime' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions()
    {
        return $this->hasMany(LoyaltyTransaction::class, 'account_id');
    }
}
