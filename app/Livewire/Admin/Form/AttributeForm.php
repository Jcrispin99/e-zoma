<?php

namespace App\Livewire\Admin\Form;

use App\Models\Attribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AttributeForm extends Component
{
    public ?int $attributeId = null;
    public bool $isEditing = false;
    public bool $redirectAfterSave = true;

    public string $name = '';
    /**
     * Array of attribute values. Each item: ['id' => ?int, 'value' => string]
     * @var array<int, array{ id: int|null, value: string }>
     */
    public array $values = [];

    public function mount(?int $attributeId = null): void
    {
        if ($attributeId) {
            $attribute = Attribute::findOrFail($attributeId);
            Gate::authorize('update_attributes', $attribute);
        } else {
            Gate::authorize('create_attributes', Attribute::class);
        }
        $this->attributeId = $attributeId;
        $this->isEditing = filled($attributeId);

        if ($this->isEditing) {
            $attribute = Attribute::with('attributeValues')->findOrFail($attributeId);
            $this->name = $attribute->name;
            $this->values = $attribute->attributeValues
                ->map(fn ($v) => ['id' => $v->id, 'value' => $v->value])
                ->toArray();
        } else {
            // Initialize with one empty value for convenience (optional)
            $this->values = [];
        }
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('attributes', 'name')->ignore($this->attributeId),
            ],
            'values' => ['nullable', 'array'],
            'values.*.id' => ['nullable', 'integer'],
            'values.*.value' => ['required', 'string', 'max:255'],
        ];
    }

    public function addValue(): void
    {
        $this->values[] = ['id' => null, 'value' => ''];
    }

    public function removeValue(int $index): void
    {
        if (isset($this->values[$index])) {
            unset($this->values[$index]);
            $this->values = array_values($this->values); // Reindex for Livewire
        }
    }

    public function save()
    {
        $data = $this->validate();

        $attribute = null;

        DB::transaction(function () use (&$attribute, $data) {
            if ($this->isEditing) {
                $attribute = Attribute::findOrFail($this->attributeId);
                $attribute->update(['name' => $data['name']]);

                $existingValueIds = [];
                if (!empty($data['values'])) {
                    foreach ($data['values'] as $valueData) {
                        if (!empty($valueData['id'])) {
                            $value = $attribute->attributeValues()->find($valueData['id']);
                            if ($value) {
                                $value->update(['value' => $valueData['value']]);
                                $existingValueIds[] = $value->id;
                            }
                        } else {
                            $newValue = $attribute->attributeValues()->create(['value' => $valueData['value']]);
                            $existingValueIds[] = $newValue->id;
                        }
                    }
                }

                // Remove values not present anymore
                $attribute->attributeValues()->whereNotIn('id', $existingValueIds)->delete();
            } else {
                $attribute = Attribute::create(['name' => $data['name']]);
                if (!empty($data['values'])) {
                    foreach ($data['values'] as $valueData) {
                        $attribute->attributeValues()->create(['value' => $valueData['value']]);
                    }
                }
            }
        });

        // Asegura que $attribute sea un objeto válido antes de usarlo
        if (!$attribute instanceof Attribute) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se pudo guardar el atributo.',
            ]);
            return;
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => $this->isEditing
                ? 'Atributo ' . $attribute->name . ' ha sido actualizado'
                : 'Atributo y sus valores han sido creados.',
        ]);

        if ($this->redirectAfterSave) {
            // Comportamiento: al actualizar -> índice, al crear -> editar
            if ($this->isEditing) {
                return redirect()->route('admin.attributes.index');
            }
            return redirect()->route('admin.attributes.edit', $attribute);
        }

        $this->dispatch('attribute:saved', id: $attribute->id);
    }

    public function render()
    {
        return view('livewire.admin.form.attribute-form');
    }
}