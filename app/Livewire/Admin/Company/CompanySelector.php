<?php

namespace App\Livewire\Admin\Company;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;

class CompanySelector extends Component
{
    /**
     * ID de la compañía seleccionada actualmente
     */
    public $selectedCompanyId;

    /**
     * Colección de todas las compañías (padres e hijas) 
     * a las que el usuario tiene acceso
     */
    public $companies;

    /**
     * Compañía actualmente seleccionada
     */
    public $currentCompany;

    /**
     * Se ejecuta cuando el componente se carga por primera vez.
     */
    public function mount()
    {
        // Cargar todas las compañías del usuario (padres e hijas en una sola colección)
        $this->companies = Auth::user()->companies()->with('children')->get()->flatMap(function ($company) {
            return collect([$company])->merge($company->children);
        });

        // Inicializar con la primera compañía disponible o desde sesión
        $this->selectedCompanyId = session('selected_company_id', $this->companies->first()?->id);
        $this->currentCompany = $this->companies->firstWhere('id', $this->selectedCompanyId);
    }

    /**
     * Cambia la compañía seleccionada
     */
    public function switchCompany($companyId)
    {
        $this->selectedCompanyId = $companyId;
        $this->currentCompany = $this->companies->firstWhere('id', $companyId);

        // Guardar en sesión
        session(['selected_company_id' => $companyId]);

        // Emitir evento para notificar el cambio
        $this->dispatch('company-switched', $companyId);
    }

    /**
     * Renderiza la vista del componente.
     */
    public function render()
    {
        return view('livewire.admin.company.company-selector');
    }
}
