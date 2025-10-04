<?php

namespace App\Livewire\Admin\Company;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;

class CompanySelector extends Component
{
    /**
     * IDs de las compañías seleccionadas actualmente
     */
    public $selectedCompanyIds = [];

    /**
     * Colección de todas las compañías (padres e hijas) 
     * a las que el usuario tiene acceso
     */
    public $companies;

    /**
     * Número de compañías seleccionadas
     */
    public $selectedCompaniesCount = 0;

    /**
     * Se ejecuta cuando el componente se carga por primera vez.
     */
    public function mount()
    {
        // Cargar todas las compañías del usuario (padres e hijas en una sola colección)
        $this->companies = Auth::user()->companies()->with('children')->get()->flatMap(function ($company) {
            return collect([$company])->merge($company->children);
        });

        // Inicializar con las compañías seleccionadas desde la sesión
        $this->selectedCompanyIds = session('selected_company_ids', []);
        $this->updateSelectedCompaniesCount();
    }

    /**
     * Cambia la compañía seleccionada
     */
    public function switchCompany($companyId)
    {
        if (in_array($companyId, $this->selectedCompanyIds)) {
            $this->selectedCompanyIds = array_diff($this->selectedCompanyIds, [$companyId]);
        } else {
            $this->selectedCompanyIds[] = $companyId;
        }

        // Guardar en sesión
        session(['selected_company_ids' => $this->selectedCompanyIds]);
        $this->updateSelectedCompaniesCount();

        // Emitir evento para notificar el cambio
        $this->dispatch('company-switched', $this->selectedCompanyIds);
    }

    /**
     * Actualiza el contador de compañías seleccionadas
     */
    private function updateSelectedCompaniesCount()
    {
        $this->selectedCompaniesCount = count($this->selectedCompanyIds);
    }

    /**
     * Renderiza la vista del componente.
     */
    public function render()
    {
        return view('livewire.admin.company.company-selector');
    }
}
