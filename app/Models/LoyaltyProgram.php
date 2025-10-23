<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyProgram extends Model
{
    protected $fillable = [
        'sequence',
        'company_id',
        'program_type',
        'applies_on',
        'trigger',
        'date_from',
        'date_to',
        'active',
        'sale_ok',
        'ecommerce_ok',
        'pos_ok',
        'limit_usage',
        'max_usage',
        'website_id',
        'key',
        'name',
    ];

    protected $casts = [
        'active' => 'bool',
        'sale_ok' => 'bool',
        'ecommerce_ok' => 'bool',
        'pos_ok' => 'bool',
        'limit_usage' => 'bool',
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(LoyaltyRule::class, 'program_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(LoyaltyReward::class, 'program_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(LoyaltyCard::class, 'program_id');
    }

    public function posConfigs(): BelongsToMany
    {
        // Pivot opcional: loyalty_program_pos_config
        return $this->belongsToMany(PosConfig::class, 'loyalty_program_pos_config');
    }
}
