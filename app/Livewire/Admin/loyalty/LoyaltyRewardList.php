<?php

namespace App\Livewire\Admin\loyalty;

use App\Models\LoyaltyProgram;
use App\Models\LoyaltyReward;
use App\Models\Variant;
use Livewire\Component;
use Illuminate\Support\Facades\Schema;

class LoyaltyRewardList extends Component
{
    public LoyaltyProgram $program;

    public bool $open = false;
    public ?int $editingId = null;

    // Form fields (esquema original)
    public string $reward_type = 'discount'; // discount | product
    public ?string $discount_mode = 'percent'; // percent | per_order | per_point
    public ?string $discount_applicability = 'order'; // order | cheapest | specific
    public ?float $discount = null;
    public ?float $discount_max_amount = null;

    public ?int $reward_product_id = null;
    public ?int $reward_product_qty = 1;

    public ?float $required_points = null;
    public bool $clear_wallet = false;
    public ?string $description = null;
    public bool $active = true;
    public array $selected_variant_ids = [];
    public ?string $name = null;

    public function mount(LoyaltyProgram $program): void
    {
        $this->program = $program;
    }

    protected function rules(): array
    {
        $requireName = \Illuminate\Support\Facades\Schema::hasColumn('loyalty_rewards', 'name');
        $rules = [
            'name' => ($requireName ? 'required' : 'nullable') . '|string|max:255',
            'reward_type' => 'required|string|in:discount,product',
            'discount_mode' => 'nullable|string|in:percent,per_order,per_point',
            'discount_applicability' => 'nullable|string|in:order,cheapest,specific',
            'discount' => 'nullable|numeric|min:0',
            'discount_max_amount' => 'nullable|numeric|min:0',
            'reward_product_id' => 'nullable|integer|exists:variants,id',
            'reward_product_qty' => 'nullable|integer|min:1',
            'required_points' => 'nullable|numeric|min:0',
            'clear_wallet' => 'boolean',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ];

        if ($this->reward_type === 'discount') {
            $rules['discount_mode'] = 'required|string|in:percent,per_order,per_point';
            $rules['discount'] = 'required|numeric|min:0';
            if ($this->discount_applicability === 'specific') {
                $rules['selected_variant_ids'] = 'array';
                $rules['selected_variant_ids.*'] = 'integer|exists:variants,id';
            }
        } else {
            $rules['reward_product_id'] = 'required|integer|exists:variants,id';
            $rules['reward_product_qty'] = 'required|integer|min:1';
        }

        return $rules;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->open = true;
        $this->editingId = null;
    }

    public function openEdit(int $rewardId): void
    {
        $reward = LoyaltyReward::query()->where('program_id', $this->program->id)->findOrFail($rewardId);
        $this->editingId = $reward->id;
        // Prefer reward_type, fall back to legacy 'type' if present
        $this->reward_type = $reward->reward_type ?? ($reward->type ?? 'discount');
        $this->name = $reward->name ?? null;
        $this->discount_mode = $reward->discount_mode;
        $this->discount_applicability = $reward->discount_applicability;
        $this->discount = $reward->discount ? (float) $reward->discount : null;
        $this->discount_max_amount = $reward->discount_max_amount ? (float) $reward->discount_max_amount : null;
        $this->reward_product_id = $reward->reward_product_id;
        $this->reward_product_qty = $reward->reward_product_qty ?: 1;
        $this->required_points = $reward->required_points ? (float) $reward->required_points : null;
        $this->clear_wallet = (bool) $reward->clear_wallet;
        $this->description = is_array($reward->description) ? json_encode($reward->description) : $reward->description;
        $this->active = (bool) $reward->active;
        $reward->load('variants');
        $this->selected_variant_ids = ($reward->discount_applicability === 'specific') ? $reward->variants->pluck('id')->toArray() : [];
        $this->open = true;
    }

    public function save(): void
    {
        $this->validate($this->rules(), [], [
            'reward_type' => 'tipo de recompensa',
            'discount_mode' => 'modo de descuento',
            'discount_applicability' => 'aplicabilidad',
            'discount' => 'descuento',
            'discount_max_amount' => 'tope de descuento',
            'reward_product_id' => 'producto regalo',
            'reward_product_qty' => 'cantidad de regalo',
            'required_points' => 'puntos requeridos',
        ]);

        $hasLegacyType = Schema::hasColumn('loyalty_rewards', 'type');
        $hasName = Schema::hasColumn('loyalty_rewards', 'name');

        $data = [
            'program_id' => $this->program->id,
            // 'company_id' eliminado por redundante
            'reward_type' => $this->reward_type,
            'discount_mode' => $this->reward_type === 'discount' ? $this->discount_mode : null,
            'discount_applicability' => $this->reward_type === 'discount' ? $this->discount_applicability : null,
            'discount' => $this->reward_type === 'discount' ? $this->discount : null,
            'discount_max_amount' => $this->reward_type === 'discount' ? $this->discount_max_amount : null,
            'reward_product_id' => $this->reward_type === 'product' ? $this->reward_product_id : null,
            'reward_product_qty' => $this->reward_type === 'product' ? $this->reward_product_qty : null,
            'required_points' => $this->required_points,
            'clear_wallet' => $this->clear_wallet,
            'description' => $this->description,
            'active' => $this->active,
        ];

        if ($hasLegacyType) {
            // Ensure legacy NOT NULL column 'type' receives value
            $data['type'] = $this->reward_type;
        }

        if ($hasName) {
            $data['name'] = trim((string) $this->name);
        }

        if ($this->editingId) {
            $reward = LoyaltyReward::query()
                ->where('program_id', $this->program->id)
                ->findOrFail($this->editingId);
            $reward->fill($data);
            $reward->save();
        } else {
            $reward = LoyaltyReward::create($data);
        }

        if ($this->reward_type === 'discount' && $this->discount_applicability === 'specific') {
            $ids = array_values(array_filter($this->selected_variant_ids ?? [], fn($id) => is_numeric($id)));
            $reward->variants()->sync($ids);
        } else {
            $reward->variants()->detach();
        }

        $this->open = false;
        $this->editingId = null;
        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Recompensa guardada',
        ]);
    }

    public function toggleActive(int $rewardId): void
    {
        $reward = LoyaltyReward::query()->where('program_id', $this->program->id)->findOrFail($rewardId);
        $reward->active = !$reward->active;
        $reward->save();
    }

    public function resetForm(): void
    {
        $this->reward_type = 'discount';
        $this->discount_mode = 'percent';
        $this->discount_applicability = 'order';
        $this->discount = null;
        $this->discount_max_amount = null;
        $this->reward_product_id = null;
        $this->reward_product_qty = 1;
        $this->required_points = null;
        $this->clear_wallet = false;
        $this->description = null;
        $this->active = true;
        $this->selected_variant_ids = [];
        $this->name = null;
    }

    public function getRewardsListProperty()
    {
        return LoyaltyReward::query()
            ->with(['rewardProduct'])
            ->where('program_id', $this->program->id)
            ->orderByDesc('id')
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.loyalty.loyalty-reward-list', [
            'rewards' => $this->rewardsList,
        ]);
    }
}
