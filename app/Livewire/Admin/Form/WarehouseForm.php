<?php

namespace App\Livewire\Admin\Form;

use Livewire\Component;
use App\Models\Warehouse;

class WarehouseForm extends Component
{
    public ?int $warehouseId = null;
    public bool $isEditing = false;

    public bool $redirectAfterSave = true;

    public string $name = '';
    public string $location = '';

    public function mount(?int $warehouseId = null): void
    {
        $this->warehouseId = $warehouseId;
        $this->isEditing = (bool) $warehouseId;

        if ($this->isEditing) {
            $warehouse = Warehouse::findOrFail($warehouseId);
            $this->name = (string) $warehouse->name;
            $this->location = (string) $warehouse->location;
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->isEditing) {
            $warehouse = Warehouse::findOrFail($this->warehouseId);
            $warehouse->update($data);

            session()->flash('swalt', [
                'icon' => 'success',
                'title' => 'Bien',
                'text' => 'Almacen actualizado correctamente.',
            ]);

            if ($this->redirectAfterSave) {
                return redirect()->route('admin.warehouses.index');
            }

            $this->dispatch('warehouse:saved', $warehouse->id);
            return;
        }

        $activeCompanyId = session('active_company_id');
        if (!$activeCompanyId) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear un almacén.',
            ]);
            return; // permanecer en la misma vista
        }

        $warehouse = Warehouse::create($data + ['company_id' => $activeCompanyId]);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Bien',
            'text' => 'Almacen guardado correctamente.',
        ]);

        if ($this->redirectAfterSave) {
            return redirect()->route('admin.warehouses.edit', $warehouse);
        }

        $this->dispatch('warehouse:saved', $warehouse->id);
    }

    public function render()
    {
        return view('livewire.admin.form.warehouse-form');
    }
}
