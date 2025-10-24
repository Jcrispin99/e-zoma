<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyaltyProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'scope',
        'is_active',
        'valid_from',
        'valid_to'
    ];

    public function earnRules()
    {
        return $this->hasMany(LoyaltyEarnRule::class, 'program_id');
    }

    public function rewards()
    {
        return $this->hasMany(LoyaltyReward::class, 'program_id');
    }
}
