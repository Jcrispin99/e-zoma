<?php

namespace App\Livewire\Admin\Company;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CompanySelector extends Component
{
    /**
     * Colección de todas las compañías (con sus sucursales)
     * a las que el usuario tiene acceso.
     * @var \Illuminate\Database\Eloquent\Collection
     */
    public $allCompanies;

    /**
     * Array con los IDs de las compañías seleccionadas.
     * Usaremos wire:model para vincularlo a los checkboxes.
     * @var array
     */
    public $selectedCompanies = [];

    /**
     * Se ejecuta cuando el componente se carga por primera vez.
     */
    public function mount()
    {
        // 1. Cargar las compañías del usuario autenticado.
        //    Mostraremos solo las compañías "padre" y cargaremos las "hijas" en la relación.
        $this->allCompanies = Auth::user()->companies()->whereNull('parent_id')->with('children')->get();

        // 2. Inicializar la selección desde el contexto (sesión).
        //    Cuando creemos CompanyContext, lo llamaremos aquí.
        //    $companyContext = app(CompanyContext::class);
        //    $this->selectedCompanies = $companyContext->getSelectedCompanyIds();
    }

    /**
     * Se ejecuta automáticamente cada vez que la propiedad $selectedCompanies cambia.
     */
    public function updatedSelectedCompanies()
    {
        // 1. Actualizar el contexto con la nueva selección.
        //    $companyContext = app(CompanyContext::class);
        //    $companyContext->setSelectedCompanies($this->selectedCompanies);

        // 2. (Opcional) Emitir un evento para que otros componentes de Livewire
        //    en la página sepan que el contexto ha cambiado y necesiten refrescarse.
        $this->dispatch('company-context-changed');

        // Para probar, podemos simplemente dumpear la selección.
        // dd($this->selectedCompanies);
    }

    /**
     * Renderiza la vista del componente.
     */
    public function render()
    {
        return view('livewire.admin.company.company-selector');
    }
}
