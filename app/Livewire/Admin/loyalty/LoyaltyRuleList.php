<?php

namespace App\Livewire\Admin\loyalty;

use App\Models\Category;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyRule;
use Livewire\Component;

class LoyaltyRuleList extends Component
{
    public LoyaltyProgram $program;

    public bool $open = false;
    public ?int $editingId = null;

    // Form fields
    public ?int $product_category_id = null;
    public ?string $product_domain = null;
    public ?int $minimum_qty = null;
    public ?float $minimum_amount = null;
    public ?string $minimum_amount_tax_mode = null; // with_tax | without_tax

    public string $mode = 'auto'; // auto | with_code
    public ?string $code = null;
    public ?string $promo_barcode = null;

    public ?string $reward_point_mode = null; // money | order
    public bool $reward_point_split = false;
    public ?float $amount_per_point = null; // dinero por 1 punto (money)
    public ?int $points_per_order = null; // puntos fijos por pedido (order)

    public bool $active = true;

    public array $selected_variant_ids = [];

    public function mount(LoyaltyProgram $program): void
    {
        $this->program = $program;
    }

    public function rules(): array
    {
        $rules = [
            'product_category_id' => 'nullable|exists:categories,id',
            'product_domain' => 'nullable|string',
            'minimum_qty' => 'nullable|integer|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'minimum_amount_tax_mode' => 'nullable|in:with_tax,without_tax',
            'mode' => 'required|in:auto,with_code',
            'code' => 'nullable|string|max:64',
            'promo_barcode' => 'nullable|string|max:128',
            'reward_point_mode' => 'nullable|in:money,order',
            'reward_point_split' => 'boolean',
            'amount_per_point' => 'nullable|numeric|min:0.01',
            'points_per_order' => 'nullable|integer|min:1',
            'active' => 'boolean',
            'selected_variant_ids' => 'nullable|array',
            'selected_variant_ids.*' => 'integer|exists:variants,id',
        ];

        if ($this->mode === 'with_code') {
            $rules['code'] = 'required|string|max:64';
        }
        if ($this->reward_point_mode === 'money') {
            $rules['amount_per_point'] = 'required|numeric|min:0.01';
            $rules['points_per_order'] = 'nullable';
        }
        if ($this->reward_point_mode === 'order') {
            $rules['points_per_order'] = 'required|integer|min:1';
            $rules['amount_per_point'] = 'nullable';
        }

        return $rules;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->open = true;
        $this->editingId = null;
    }

    public function openEdit(int $ruleId): void
    {
        $rule = LoyaltyRule::query()->where('program_id', $this->program->id)->findOrFail($ruleId);
        $this->editingId = $rule->id;
        $this->product_category_id = $rule->product_category_id;
        $this->product_domain = $rule->product_domain;
        $this->minimum_qty = $rule->minimum_qty;
        $this->minimum_amount = $rule->minimum_amount ? (float) $rule->minimum_amount : null;
        $this->minimum_amount_tax_mode = $rule->minimum_amount_tax_mode;
        $this->mode = $rule->mode ?? 'auto';
        $this->code = $rule->code;
        $this->promo_barcode = $rule->promo_barcode;
        $this->reward_point_mode = $rule->reward_point_mode;
        $this->reward_point_split = (bool) $rule->reward_point_split;
        $this->amount_per_point = $rule->amount_per_point ? (float) $rule->amount_per_point : null;
        $this->points_per_order = $rule->points_per_order ? (int) $rule->points_per_order : null;
        $this->active = (bool) $rule->active;
        $this->selected_variant_ids = $rule->variants()->pluck('variants.id')->toArray();
        $this->open = true;
    }

    public function save(): void
    {
        $this->validate($this->rules(), [], [
            'product_category_id' => 'categoría',
            'product_domain' => 'dominio de producto',
            'minimum_qty' => 'cantidad mínima',
            'minimum_amount' => 'monto mínimo',
            'minimum_amount_tax_mode' => 'modo de impuestos',
            'mode' => 'modo',
            'code' => 'código',
            'promo_barcode' => 'código de barras',
            'amount_per_point' => 'monto por punto',
            'points_per_order' => 'puntos por pedido',
        ]);

        $data = [
            'program_id' => $this->program->id,
            'product_category_id' => $this->product_category_id,
            'product_tag_id' => null,
            'product_domain' => $this->product_domain,
            'minimum_qty' => $this->minimum_qty ?? 0,
            'minimum_amount' => $this->minimum_amount,
            'minimum_amount_tax_mode' => $this->minimum_amount_tax_mode,
            'mode' => $this->mode,
            'code' => $this->code,
            'promo_barcode' => $this->promo_barcode,
            'reward_point_mode' => $this->reward_point_mode,
            'reward_point_split' => (bool) $this->reward_point_split,
            'amount_per_point' => $this->reward_point_mode === 'money' ? $this->amount_per_point : null,
            'points_per_order' => $this->reward_point_mode === 'order' ? $this->points_per_order : null,
            'active' => $this->active,
        ];

        if ($this->editingId) {
            $rule = LoyaltyRule::query()
                ->where('program_id', $this->program->id)
                ->findOrFail($this->editingId);
            $rule->update($data);
        } else {
            $rule = LoyaltyRule::create($data);
        }

        $rule->variants()->sync($this->selected_variant_ids ?? []);

        $this->open = false;
        $this->editingId = null;
        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Regla guardada',
        ]);
    }

    public function toggleActive(int $ruleId): void
    {
        $rule = LoyaltyRule::query()->where('program_id', $this->program->id)->findOrFail($ruleId);
        $rule->active = !$rule->active;
        $rule->save();
    }

    public function resetForm(): void
    {
        $this->product_category_id = null;
        $this->product_domain = null;
        $this->minimum_qty = null;
        $this->minimum_amount = null;
        $this->minimum_amount_tax_mode = null;
        $this->mode = 'auto';
        $this->code = null;
        $this->promo_barcode = null;
        $this->reward_point_mode = null;
        $this->reward_point_split = false;
        $this->amount_per_point = null;
        $this->points_per_order = null;
        $this->active = true;
        $this->selected_variant_ids = [];
    }

    public function getRulesListProperty()
    {
        return LoyaltyRule::query()
            ->with(['category'])
            ->where('program_id', $this->program->id)
            ->orderByDesc('id')
            ->get();
    }

    public function getCategoriesProperty()
    {
        return Category::query()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.admin.loyalty.loyalty-rule-list', [
            'rules' => $this->rulesList,
            'categories' => $this->categories,
        ]);
    }
}
