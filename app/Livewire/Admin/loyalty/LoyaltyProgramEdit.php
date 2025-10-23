<?php

namespace App\Livewire\Admin\loyalty;

use Livewire\Component;
use App\Models\LoyaltyProgram;
use App\Models\Company;

class LoyaltyProgramEdit extends Component
{
    public LoyaltyProgram $program;

    public $company_id;
    public $name; // nuevo campo obligatorio
    public $program_type;
    public $applies_on;
    public $trigger;

    public $date_from;
    public $date_to;
    public $active;

    public $sale_ok;
    public $ecommerce_ok;
    public $pos_ok;

    public $limit_usage;
    public $max_usage;

    public $website_id;

    public $companies = [];

    // modales removidos; tablas visibles en el editar

    protected $rules = [
        'company_id' => 'nullable|exists:companies,id',
        'name' => 'required|string|max:120',
        'program_type' => 'required|string',
        'applies_on' => 'required|string|in:current,future,both',
        'trigger' => 'nullable|string|in:auto,with_code',
        'date_from' => 'nullable|date',
        'date_to' => 'nullable|date|after_or_equal:date_from',
        'active' => 'boolean',
        'sale_ok' => 'boolean',
        'ecommerce_ok' => 'boolean',
        'pos_ok' => 'boolean',
        'limit_usage' => 'boolean',
        'max_usage' => 'nullable|integer|min:1',
        'website_id' => 'nullable|integer',
    ];

    public function mount(LoyaltyProgram $program)
    {
        $this->program = $program;

        $this->companies = Company::query()->orderBy('name')->get();

        $this->company_id = $program->company_id;
        $this->name = $program->name;
        $this->program_type = $program->program_type;
        $this->applies_on = $program->applies_on;
        $this->trigger = $program->trigger;
        $this->date_from = optional($program->date_from)?->format('Y-m-d');
        $this->date_to = optional($program->date_to)?->format('Y-m-d');
        $this->active = (bool) $program->active;
        $this->sale_ok = (bool) $program->sale_ok;
        $this->ecommerce_ok = (bool) $program->ecommerce_ok;
        $this->pos_ok = (bool) $program->pos_ok;
        $this->limit_usage = (bool) $program->limit_usage;
        $this->max_usage = $program->max_usage;
        $this->website_id = $program->website_id;
    }

    public function update()
    {
        $data = $this->validate();

        // Normalizar entradas vacías a null
        $data['company_id'] = ($data['company_id'] ?? null) ?: (session('active_company_id') ?: null);
        $data['max_usage'] = isset($data['max_usage']) && $data['max_usage'] !== '' ? (int) $data['max_usage'] : null;
        $data['website_id'] = isset($data['website_id']) && $data['website_id'] !== '' ? (int) $data['website_id'] : null;
        $data['trigger'] = ($data['trigger'] ?? null) ?: null;

        $this->program->update($data);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Programa actualizado',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.loyalty.loyalty-program-edit');
    }
}
