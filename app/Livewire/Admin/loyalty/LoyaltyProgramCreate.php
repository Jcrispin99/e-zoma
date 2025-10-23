<?php

namespace App\Livewire\Admin\loyalty;

use Livewire\Component;
use App\Models\LoyaltyProgram;
use App\Models\Company;
use Illuminate\Support\Str;

class LoyaltyProgramCreate extends Component
{
    public $company_id;
    public $name; // nuevo campo obligatorio
    public $program_type = 'loyalty';
    public $applies_on = 'current';
    public $trigger = 'auto';

    public $date_from;
    public $date_to;
    public $active = true;

    public $sale_ok = true;
    public $ecommerce_ok = false;
    public $pos_ok = false;

    public $limit_usage = false;
    public $max_usage;

    public $website_id;

    public $companies = [];

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

    public function mount()
    {
        $this->companies = Company::query()->orderBy('name')->get();
    }

    public function save()
    {
        $data = $this->validate();

        // Normalizar entradas vacías a null
        $data['company_id'] = ($data['company_id'] ?? null) ?: (session('active_company_id') ?: null);
        $data['max_usage'] = isset($data['max_usage']) && $data['max_usage'] !== '' ? (int) $data['max_usage'] : null;
        $data['website_id'] = isset($data['website_id']) && $data['website_id'] !== '' ? (int) $data['website_id'] : null;
        $data['trigger'] = ($data['trigger'] ?? null) ?: null;

        // Generar clave única requerida por la BD
        $data['key'] = Str::uuid()->toString();

        $program = LoyaltyProgram::create($data);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Programa creado',
        ]);

        return redirect()->route('admin.loyalty-programs.edit', $program);
    }

    public function render()
    {
        return view('livewire.admin.loyalty.loyalty-program-create');
    }
}
