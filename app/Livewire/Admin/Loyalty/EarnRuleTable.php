<?php

namespace App\Livewire\Admin\Loyalty;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\LoyaltyEarnRule;
use App\Models\LoyaltyProgram;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Product;

class EarnRuleTable extends Component
{
    use WithPagination;

    public LoyaltyProgram $program;

    public bool $modalOpen = false;

    // Form fields
    public string $name = '';
    public ?string $basis = null; // 'per_amount' | 'per_unit' | 'per_order'
    public ?float $points_value = null; // unified points input (puede ser decimal)
    public ?float $points_per_sol = null; // mapped on save
    public ?float $points_per_unit = null; // mapped on save
    public ?int $points_per_order = null; // mapped on save
    public ?int $min_qty = null;
    public ?float $min_amount = null;
    public bool $is_active = true;
    public int $priority = 0;
    public ?string $description = null;
    public ?string $scope_type = null; // derived automatically from category/variants

    // Campos dependientes del alcance
    public ?int $category_id = null;
    public array $variant_ids = [];

    // Edit state
    public ?int $editingId = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'basis' => ['required', Rule::in(['per_amount', 'per_unit', 'per_order'])],
            'points_value' => ['required', 'numeric', 'min:0'],
            'min_qty' => ['nullable', 'integer', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'priority' => ['integer', 'min:0'],
            'description' => ['nullable', 'string'],
            // Alcance (sin checkboxes)
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'variant_ids' => ['nullable', 'array'],
            'variant_ids.*' => ['integer', 'exists:variants,id'],
        ];
    }

    public function mount(LoyaltyProgram $program)
    {
        $this->program = $program;
    }

    public function updated($propertyName): void
    {
        // Autogenerar nombre si está vacío
        if (trim($this->name) === '') {
            $this->name = $this->generateAutoName();
        }
    }

    private function generateAutoName(): string
    {
        $basisLabel = match ($this->basis) {
            'per_amount' => 'Puntos por S/ gastados',
            'per_unit' => 'Puntos por unidad pagada',
            'per_order' => 'Puntos por orden',
            default => 'Regla de puntos',
        };

        $pointsPart = '';
        if (!is_null($this->points_value)) {
            $pointsPart = " ({$this->points_value} pts)";
        }

        // Alcance
        $scopeLabel = 'Todos los productos';
        $detail = '';
        if ($this->category_id) {
            $scopeLabel = 'Categoría';
            $detail = " (Categoría #{$this->category_id})";
        } elseif (!empty($this->variant_ids)) {
            $scopeLabel = 'Variantes';
            $detail = ' (' . count($this->variant_ids) . ' variante(s))';
        }

        $conditions = [];
        if (!is_null($this->min_qty) && $this->min_qty > 0) {
            $conditions[] = "min_qty={$this->min_qty}";
        }
        if (!is_null($this->min_amount) && $this->min_amount > 0) {
            $conditions[] = "min_amount={$this->min_amount}";
        }
        $condPart = empty($conditions) ? '' : ' [' . implode(', ', $conditions) . ']';

        return $basisLabel . $pointsPart . ' - ' . $scopeLabel . $detail . $condPart;
    }

    public function openModal(): void
    {
        $this->editingId = null;
        $this->modalOpen = true;
    }

    public function edit(int $id): void
    {
        $rule = LoyaltyEarnRule::findOrFail($id);
        $this->editingId = $rule->id;
        $this->modalOpen = true;

        $this->name = $rule->name ?? '';
        $this->basis = $rule->basis;
        // map points
        $this->points_value = match ($rule->basis) {
            'per_amount' => (float) ($rule->points_per_sol ?? 0),
            'per_unit' => (float) ($rule->points_per_unit ?? 0),
            'per_order' => (float) ($rule->points_per_order ?? 0),
            default => null,
        };
        $this->min_qty = $rule->min_qty;
        $this->min_amount = $rule->min_amount;
        $this->is_active = (bool) $rule->is_active;
        $this->priority = $rule->priority ?? 0;
        $this->description = $rule->description;

        // Alcance
        $this->scope_type = $rule->scope_type; // informativo
        $this->category_id = $rule->category_id;
        $this->variant_ids = $rule->variants()->select('variants.id as id')->pluck('id')->toArray();
    }

    public function closeModal(): void
    {
        $this->modalOpen = false;
    }

    public function submit(): void
    {
        if ($this->editingId) {
            $this->update();
        } else {
            $this->save();
        }
    }

    public function save(): void
    {
        $this->validate();

        // map unified points to DB columns according to basis
        $this->points_per_sol = $this->basis === 'per_amount' ? $this->points_value : null;
        $this->points_per_unit = $this->basis === 'per_unit' ? $this->points_value : null;
        $this->points_per_order = $this->basis === 'per_order' ? $this->points_value : null;

        // set scope_type automatically
        $this->scope_type = $this->category_id ? 'category' : (!empty($this->variant_ids) ? 'variant' : 'all');

        $rule = LoyaltyEarnRule::create([
            'program_id' => $this->program->id,
            'name' => $this->name,
            'basis' => $this->basis,
            'points_per_sol' => $this->points_per_sol,
            'points_per_unit' => $this->points_per_unit,
            'points_per_order' => $this->points_per_order,
            'min_qty' => $this->min_qty,
            'min_amount' => $this->min_amount,
            'is_active' => (bool) $this->is_active,
            'priority' => $this->priority,
            'description' => $this->description,
            'scope_type' => $this->scope_type,
            'category_id' => $this->category_id,
        ]);

        // Sync variants
        if (!empty($this->variant_ids)) {
            $rule->variants()->sync($this->variant_ids);
        } else {
            $rule->variants()->sync([]);
        }

        $this->resetState();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Regla creada',
            'text' => 'La regla de acumulación se ha creado exitosamente.',
        ]);
    }

    public function update(): void
    {
        $this->validate();

        $rule = LoyaltyEarnRule::findOrFail($this->editingId);

        // map points
        $this->points_per_sol = $this->basis === 'per_amount' ? $this->points_value : null;
        $this->points_per_unit = $this->basis === 'per_unit' ? $this->points_value : null;
        $this->points_per_order = $this->basis === 'per_order' ? $this->points_value : null;

        // scope_type
        $this->scope_type = $this->category_id ? 'category' : (!empty($this->variant_ids) ? 'variant' : 'all');

        $rule->update([
            'name' => $this->name,
            'basis' => $this->basis,
            'points_per_sol' => $this->points_per_sol,
            'points_per_unit' => $this->points_per_unit,
            'points_per_order' => $this->points_per_order,
            'min_qty' => $this->min_qty,
            'min_amount' => $this->min_amount,
            'is_active' => (bool) $this->is_active,
            'priority' => $this->priority,
            'description' => $this->description,
            'scope_type' => $this->scope_type,
            'category_id' => $this->category_id,
        ]);

        // Sync variants
        if (!empty($this->variant_ids)) {
            $rule->variants()->sync($this->variant_ids);
        } else {
            $rule->variants()->sync([]);
        }

        $this->resetState();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Regla actualizada',
            'text' => 'La regla se ha actualizado correctamente.',
        ]);
    }

    private function resetState(): void
    {
        $this->reset(['modalOpen', 'editingId', 'name', 'basis', 'points_value', 'points_per_sol', 'points_per_unit', 'points_per_order', 'min_qty', 'min_amount', 'is_active', 'priority', 'description', 'scope_type', 'category_id', 'variant_ids']);
        $this->resetPage();
    }

    public function render()
    {
        $rules = LoyaltyEarnRule::where('program_id', $this->program->id)
            ->orderBy('priority')
            ->latest('id')
            ->paginate(10);

        return view('livewire.admin.loyalty.earn-rule-table', compact('rules'));
    }
}
