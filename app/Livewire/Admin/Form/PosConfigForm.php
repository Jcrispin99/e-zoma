<?php

namespace App\Livewire\Admin\Form;

use Livewire\Component;
use App\Models\PosConfig;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\Journal;
use App\Models\Tax;
use Illuminate\Validation\Rule;

class PosConfigForm extends Component
{
    public ?int $posConfigId = null;
    public bool $isEditing = false;

    public bool $redirectAfterSave = true;

    public string $name = '';
    public ?int $warehouse_id = null;
    public ?int $receipt_journal_id = null;
    public ?int $invoice_journal_id = null;
    public ?int $default_customer_id = null;
    public ?int $default_tax_id = null;
    public bool $is_active = true;
    public bool $apply_tax = true;
    public float $tax_rate = 0.18;
    public bool $prices_include_tax = false;

    /** @var array<int, array{id:int, name:string}> */
    public array $warehouseOptions = [];
    /** @var array<int, array{id:int, name:string}> */
    public array $customerOptions = [];
    /** @var array<int, array{id:int, label:string}> */
    public array $journalOptions = [];
    /** @var array<int, array{id:int, label:string}> */
    public array $taxOptions = [];
    /** @var array<int, array{rate_decimal:float, price_inclusive:bool}> */
    public array $taxMetaById = [];

    public function mount(?int $posConfigId = null): void
    {
        $this->posConfigId = $posConfigId;
        $this->isEditing = (bool) $posConfigId;

        // Preload options
        $this->loadOptions($posConfigId);

        if ($this->isEditing) {
            $pos = PosConfig::query()->findOrFail($posConfigId);
            $this->name = (string) $pos->name;
            $this->warehouse_id = $pos->warehouse_id;
            $this->receipt_journal_id = $pos->receipt_journal_id;
            $this->invoice_journal_id = $pos->invoice_journal_id;
            $this->default_customer_id = $pos->default_customer_id;
            $this->default_tax_id = $pos->default_tax_id;
            $this->is_active = (bool) $pos->is_active;
            $this->apply_tax = (bool) ($pos->apply_tax ?? true);
            $this->tax_rate = (float) ($pos->tax_rate ?? 0.18);
            $this->prices_include_tax = (bool) ($pos->prices_include_tax ?? false);
        } else {
            // Defaults similar to create view
            $this->is_active = true;
            $this->apply_tax = true;
            $this->tax_rate = 0.18;
            $this->prices_include_tax = false;

            // Select first warehouse by default (if available)
            if (!$this->warehouse_id && !empty($this->warehouseOptions)) {
                $this->warehouse_id = $this->warehouseOptions[0]['id'] ?? null;
            }
        }
    }

    protected function loadOptions(?int $posConfigId = null): void
    {
        $companyId = null;
        if ($posConfigId) {
            $companyId = PosConfig::query()->findOrFail($posConfigId)->company_id;
        } else {
            $companyId = session('active_company_id');
        }

        $warehouseQuery = Warehouse::query()->select(['id', 'name']);
        if ($companyId) {
            $warehouseQuery->where('company_id', $companyId);
        }
        $this->warehouseOptions = $warehouseQuery
            ->orderBy('name')
            ->get()
            ->map(fn($w) => ['id' => (int) $w->id, 'name' => (string) $w->name])
            ->toArray();

        $this->customerOptions = Customer::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn($c) => ['id' => (int) $c->id, 'name' => (string) $c->name])
            ->toArray();

        $this->journalOptions = Journal::query()
            ->where('type', 'sale')
            ->with('sequence')
            ->orderBy('name')
            ->get()
            ->map(function ($j) {
                $seq = $j->sequence;
                $seqLabel = '';
                if ($seq) {
                    $pad = str_pad((string) $seq->next_number, (int) $seq->sequence_size, '0', STR_PAD_LEFT);
                    $seqLabel = " — Secuencia: #{$seq->id} Próximo {$pad}";
                }
                return [
                    'id' => (int) $j->id,
                    'label' => (string) ($j->name . ' (' . $j->code . ')' . $seqLabel),
                ];
            })
            ->toArray();

        $taxes = Tax::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $this->taxOptions = $taxes->map(function ($t) {
            $label = $t->name . ' — ' . number_format((float) $t->rate_percent, 2) . '% ' . ($t->is_price_inclusive ? 'incluido' : 'excluido') . ' — ' . $t->affectation_type_code;
            if ($t->is_default) {
                $label .= ' (por defecto)';
            }
            return [
                'id' => (int) $t->id,
                'label' => (string) $label,
            ];
        })->toArray();

        $this->taxMetaById = $taxes->mapWithKeys(function ($t) {
            return [
                (int) $t->id => [
                    'rate_decimal' => (float) ($t->rate_percent / 100),
                    'price_inclusive' => (bool) $t->is_price_inclusive,
                ],
            ];
        })->toArray();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'receipt_journal_id' => ['required', 'exists:journals,id'],
            'invoice_journal_id' => ['required', 'exists:journals,id'],
            'default_customer_id' => ['required', 'exists:customers,id'],
            'default_tax_id' => ['nullable', 'exists:taxes,id'],
            'is_active' => ['boolean'],
            // IGV
            'apply_tax' => ['boolean'],
            'tax_rate' => ['numeric', 'min:0', 'max:1'],
            'prices_include_tax' => ['boolean'],
        ];
    }

    public function updatedDefaultTaxId($value): void
    {
        $id = (int) ($value ?? 0);
        if ($id && isset($this->taxMetaById[$id])) {
            $meta = $this->taxMetaById[$id];
            $this->tax_rate = (float) number_format($meta['rate_decimal'], 2, '.', '');
            $this->apply_tax = $meta['rate_decimal'] != 0.0;
            $this->prices_include_tax = (bool) $meta['price_inclusive'];
        }
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->isEditing) {
            $pos = PosConfig::query()->findOrFail($this->posConfigId);
            $pos->update($data);

            session()->flash('swalt', [
                'icon' => 'success',
                'title' => '¡Bien hecho!',
                'text' => 'Configuración de POS ha sido actualizada',
            ]);

            if ($this->redirectAfterSave) {
                return redirect()->route('admin.posconfig.edit', $pos);
            }
        } else {
            $activeCompanyId = session('active_company_id');
            if (!$activeCompanyId) {
                session()->flash('swalt', [
                    'icon' => 'error',
                    'title' => 'Error',
                    'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear la configuración POS.',
                ]);
                return null;
            }

            $data['company_id'] = $activeCompanyId;
            $pos = PosConfig::query()->create($data);

            session()->flash('swalt', [
                'icon' => 'success',
                'title' => '¡Bien hecho!',
                'text' => 'Configuración de POS ha sido creada',
            ]);

            if ($this->redirectAfterSave) {
                return redirect()->route('admin.posconfig.edit', $pos);
            }
        }

        // Emit in-page event if not redirecting
        $this->dispatch('posconfig:saved', $this->isEditing ? $this->posConfigId : ($pos->id ?? null));
    }

    public function render()
    {
        return view('livewire.admin.form.pos-config-form');
    }
}