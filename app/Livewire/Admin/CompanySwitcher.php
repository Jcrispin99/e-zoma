<?php

namespace App\Livewire\Admin;

use App\Models\Company;
use Livewire\Component;

class CompanySwitcher extends Component
{
    public $selectedCompanyId;
    public $companies;
    public $currentCompany;

    public function mount()
    {
        $this->companies = Company::all();
        $this->selectedCompanyId = session('selected_company_id', $this->companies->first()?->id);
        $this->currentCompany = $this->companies->find($this->selectedCompanyId);
    }

    public function switchCompany($companyId)
    {
        $this->selectedCompanyId = $companyId;

        $this->validate([
            'selectedCompanyId' => 'required|exists:companies,id'
        ]);

        session(['selected_company_id' => $this->selectedCompanyId]);
        $this->currentCompany = $this->companies->find($this->selectedCompanyId);

        // Emitir evento para notificar el cambio
        $this->dispatch('company-switched', $this->selectedCompanyId);

        // Refrescar la página para aplicar los cambios
        return redirect()->to(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.admin.company-switcher');
    }
}
