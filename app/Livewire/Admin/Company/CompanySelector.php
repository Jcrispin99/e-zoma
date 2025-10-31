<?php

namespace App\Livewire\Admin\Company;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;

class CompanySelector extends Component
{
    /**
     * IDs de las compañías seleccionadas (visibles en la sesión)
     */
    public $selectedCompanyIds = [];

    /**
     * ID de la compañía activa (donde se crean los registros)
     */
    public $activeCompanyId;

    /**
     * Colección de todas las compañías (padres e hijas) 
     * a las que el usuario tiene acceso
     */
    public $companies;

    /**
     * Nombre de la compañía principal a mostrar
     */
    public $mainCompanyName;

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
        $this->activeCompanyId = session('active_company_id');

        // Si no hay compañías seleccionadas, seleccionar la primera por defecto
        if (empty($this->selectedCompanyIds) || empty($this->activeCompanyId)) {
            $firstCompany = Auth::user()->companies()->orderBy('id')->first();

            if ($firstCompany) {
                $this->selectedCompanyIds = [$firstCompany->id];
                $this->activeCompanyId = $firstCompany->id;
            } elseif ($this->companies->isNotEmpty()) {
                // Si no tiene compañías padre, usar la primera disponible
                $this->selectedCompanyIds = [$this->companies->first()->id];
                $this->activeCompanyId = $this->companies->first()->id;
            }

            $this->saveToSession();
        }

        // Asegurar que la compañía activa esté en las seleccionadas
        if ($this->activeCompanyId && !in_array($this->activeCompanyId, $this->selectedCompanyIds)) {
            // Si la activa no está seleccionada, agregarla
            $this->selectedCompanyIds[] = $this->activeCompanyId;
            $this->saveToSession();
        } elseif (!$this->activeCompanyId && !empty($this->selectedCompanyIds)) {
            // Si no hay activa pero sí hay seleccionadas, usar la primera
            $this->activeCompanyId = $this->selectedCompanyIds[0];
            $this->saveToSession();
        }

        $this->setMainCompanyName();
    }

    /**
     * Establece el nombre de la compañía principal que se mostrará en el selector.
     */
    private function setMainCompanyName()
    {
        $activeCompany = $this->companies->firstWhere('id', $this->activeCompanyId);

        if ($activeCompany) {
            $this->mainCompanyName = $activeCompany->trade_name;
        } else {
            $this->mainCompanyName = 'Seleccionar Compañía';
        }
    }

    /**
     * Maneja el click en el checkbox (toggle selección)
     */
    public function toggleCompany($companyId)
    {
        $isCurrentlySelected = in_array($companyId, $this->selectedCompanyIds);

        if ($isCurrentlySelected) {
            // Prevenir que se deseleccionen todas las compañías
            if (count($this->selectedCompanyIds) === 1) {
                return;
            }

            // Eliminar de seleccionadas
            $this->selectedCompanyIds = array_values(array_diff($this->selectedCompanyIds, [$companyId]));

            // Si era la activa, cambiar a la primera seleccionada
            if ($this->activeCompanyId == $companyId && !empty($this->selectedCompanyIds)) {
                $this->activeCompanyId = $this->selectedCompanyIds[0];
            }
        } else {
            // Agregar a seleccionadas
            $this->selectedCompanyIds[] = $companyId;
        }

        $this->saveToSession();
        $this->setMainCompanyName();
        $this->dispatchChanges();
    }

    /**
     * Maneja el click en el nombre (cambiar compañía activa)
     */
    public function setActiveCompany($companyId)
    {
        // IMPORTANTE: La compañía activa SIEMPRE debe estar seleccionada
        if (!in_array($companyId, $this->selectedCompanyIds)) {
            $this->selectedCompanyIds[] = $companyId;
        }

        $this->activeCompanyId = $companyId;

        $this->saveToSession();
        $this->setMainCompanyName();
        $this->dispatchChanges();
    }

    /**
     * Guarda el estado en la sesión
     */
    private function saveToSession()
    {
        session([
            'selected_company_ids' => $this->selectedCompanyIds,
            'active_company_id' => $this->activeCompanyId,
        ]);
    }

    /**
     * Emite eventos para notificar cambios
     */
    private function dispatchChanges()
    {
        $this->dispatch('company-changed', [
            'selected_companies' => $this->selectedCompanyIds,
            'active_company' => $this->activeCompanyId,
        ]);
        $this->js('window.location.reload()');
    }

    /**
     * Verifica si una compañía es la activa
     */
    public function isActive($companyId)
    {
        return $this->activeCompanyId == $companyId;
    }

    /**
     * Verifica si una compañía está seleccionada
     */
    public function isSelected($companyId)
    {
        return in_array($companyId, $this->selectedCompanyIds);
    }

    /**
     * Renderiza la vista del componente.
     */
    public function render()
    {
        return view('livewire.admin.company.company-selector');
    }
}
