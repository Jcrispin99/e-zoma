<?php

namespace App\Livewire\Admin\Loyalty;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyProgram;
use Illuminate\Validation\Rule;
use App\Models\Product;

class RewardTable extends Component
{
    use WithPagination;

    public LoyaltyProgram $program;

    public bool $modalOpen = false;

    // Form fields
    public string $name = '';
    public ?string $reward_type = null; // 'discount' | 'free_product' | 'free_shipping'
    public ?int $points_cost = null;
    public bool $consume_all_points = false;
    public bool $is_active = true;
    public int $priority = 0;
    public ?string $description = null;

    // Campos específicos según el tipo de recompensa
    public ?int $reward_product_id = null; // usado para producto gratis o producto de descuento (reporte)

    // Descuento
    public ?string $discount_method = null; // 'percent' | 'soles_per_point' | 'soles_fixed'
    public ?string $discount_scope = null;  // 'order' | 'cheapest_product' | 'specific_product'
    public ?float $discount_value = null;   // entrada unificada para la cantidad de descuento
    public ?float $max_discount_amount = null; // tope en S/ para orden o producto más barato

    // Alcance específico (producto específico)
    public ?int $discount_category_id = null;
    public array $discount_variant_ids = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'reward_type' => ['required', Rule::in(['discount', 'free_product', 'free_shipping'])],
            'points_cost' => ['nullable', 'integer', 'min:0'],
            'consume_all_points' => ['boolean'],
            'is_active' => ['boolean'],
            'priority' => ['integer', 'min:0'],
            'description' => ['nullable', 'string'],
            // Condicionales base
            'reward_product_id' => ['nullable', 'integer', 'exists:products,id', 'required_if:reward_type,free_product'],
        ];
    }

    public function mount(LoyaltyProgram $program)
    {
        $this->program = $program;
    }

    public function openModal(): void
    {
        $this->modalOpen = true;
    }

    public function closeModal(): void
    {
        $this->modalOpen = false;
    }

    public function save(): void
    {
        // Reglas dinámicas según tipo
        $rules = $this->rules();
        if ($this->reward_type === 'discount') {
            $rules = array_merge($rules, [
                'discount_method' => ['required', Rule::in(['percent', 'soles_per_point', 'soles_fixed'])],
                'discount_scope' => ['required', Rule::in(['order', 'cheapest_product', 'specific_product'])],
                'discount_value' => ['required', 'numeric', 'min:0'],
                // tope cuando aplica a orden o producto más barato
                'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
                // específico
                'discount_category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'discount_variant_ids' => ['nullable', 'array'],
                'discount_variant_ids.*' => ['integer', 'exists:variants,id'],
            ]);
        }

        $this->validate($rules);

        // Preparar payload
        $discountPercent = null;
        $solesPerPoint = null;
        $fixedAmount = null;

        if ($this->reward_type === 'discount') {
            switch ($this->discount_method) {
                case 'percent':
                    $discountPercent = (float) $this->discount_value;
                    // Limitar 0-100 por seguridad
                    if ($discountPercent < 0) $discountPercent = 0; 
                    if ($discountPercent > 100) $discountPercent = 100;
                    break;
                case 'soles_per_point':
                    $solesPerPoint = (float) $this->discount_value;
                    break;
                case 'soles_fixed':
                    $fixedAmount = (float) $this->discount_value;
                    break;
            }
        }

        $reward = LoyaltyReward::create([
            'program_id' => $this->program->id,
            'name' => $this->name,
            'reward_type' => $this->reward_type,
            'points_cost' => $this->points_cost,
            'consume_all_points' => (bool) $this->consume_all_points,
            'is_active' => (bool) $this->is_active,
            'priority' => $this->priority,
            'description' => $this->description,
            // producto: gratis o de descuento (reporte)
            'reward_product_id' => $this->reward_type === 'free_product' ? $this->reward_product_id : ($this->reward_type === 'discount' ? $this->reward_product_id : null),
            // descuento
            'discount_method' => $this->reward_type === 'discount' ? $this->discount_method : null,
            'discount_scope' => $this->reward_type === 'discount' ? $this->discount_scope : null,
            'discount_percent' => $discountPercent,
            'soles_per_point' => $solesPerPoint,
            'fixed_amount' => $fixedAmount,
            'max_discount_amount' => ($this->reward_type === 'discount' && in_array($this->discount_scope, ['order', 'cheapest_product'])) ? $this->max_discount_amount : null,
            // alcance específico
            'discount_category_id' => ($this->reward_type === 'discount' && $this->discount_scope === 'specific_product') ? $this->discount_category_id : null,
        ]);

        // Sincronizar variantes si es producto específico
        if ($this->reward_type === 'discount' && $this->discount_scope === 'specific_product') {
            $reward->variants()->sync($this->discount_variant_ids ?: []);
        } else {
            // limpiar por si acaso
            $reward->variants()->sync([]);
        }

        $this->reset(['modalOpen', 'name', 'reward_type', 'points_cost', 'consume_all_points', 'is_active', 'priority', 'description', 'reward_product_id', 'discount_method', 'discount_scope', 'discount_value', 'max_discount_amount', 'discount_category_id', 'discount_variant_ids']);
        $this->resetPage();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Recompensa creada',
            'text' => 'La recompensa se ha creado exitosamente.',
        ]);
    }

    public function render()
    {
        $rewards = LoyaltyReward::where('program_id', $this->program->id)
            ->orderBy('priority')
            ->latest('id')
            ->paginate(10);

        $products = Product::select('id', 'name')
            ->orderBy('name')
            ->limit(100)
            ->get();

        return view('livewire.admin.loyalty.reward-table', compact('rewards', 'products'));
    }
}