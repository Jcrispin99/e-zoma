<?php

namespace App\Livewire\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CompanyFilterable
{
    public function applyCompanyFilter(Builder $query): Builder
    {
        $selectedCompanyIds = session()->get('selected_company_ids', []);

        if (!empty($selectedCompanyIds)) {
            return $query->whereIn('company_id', $selectedCompanyIds);
        }

        return $query;
    }
}