<?php

namespace App\Livewire\Admin\sales;

use App\Facades\Kardex;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\Variant;
use Livewire\Component;
use App\Models\Quote;
use App\Models\Journal;
use App\Services\SequenceService;
use App\Services\KardexServices;
use App\Models\Tax;

class SaleCreate extends Component
{
    public $correlative = '';

    public $date;

    public $quote_id;

    protected $queryString = ['quote_id'];

    public $warehouse_id;

    public $customer_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];

    public $taxes = [];
    public $default_tax_id = null;

    public $journals = [];
    public $journal_id;

    public function boot()
    {
        $this->withValidator(function ($validator) {
            if ($validator->fails()) {

                $errors = $validator->errors()->toArray();

                $html = "<ul class='text-left'>";

                foreach ($errors as $error) {
                    $html .= "<li>{$error[0]}</li>";
                }

                $html .= "</ul>";

                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Error de validación',
                    'html' => $html,
                ]);
            }
        });
    }

    public function mount()
    {
        $activeCompanyId = session('active_company_id');
        $this->date = now();

        $this->journals = Journal::where('type', 'sale')
            ->with('sequence')
            ->orderBy('name')
            ->get();

        $journalsCol = collect($this->journals);
        if ($journalsCol->isNotEmpty()) {
            $first = $journalsCol->first();
            $this->journal_id = $first ? $first->id : null;
            $this->updatePreview();
        }

        // Cargar impuestos activos (dinámicos) y definir uno por defecto
        $this->taxes = Tax::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'rate_percent' => (float) $t->rate_percent,
                    'is_price_inclusive' => (bool) $t->is_price_inclusive,
                    'is_default' => (bool) $t->is_default,
                    'invoice_label' => $t->invoice_label ?? null,
                ];
            })
            ->toArray();
        $default = collect($this->taxes)->firstWhere('is_default', true) ?? collect($this->taxes)->first();
        $this->default_tax_id = $default['id'] ?? null;

        if ($this->quote_id) {
            $quote = Quote::with('variants.product')->find($this->quote_id);
            if ($quote) {
                if ($quote->sales()->exists() || (($quote->status ?? null) === 'converted')) {
                    $this->dispatch('swal', [
                        'icon' => 'warning',
                        'title' => 'Cotización ya convertida',
                        'text' => 'Esta cotización ya tiene una venta vinculada.',
                    ]);
                    $this->quote_id = null;
                } else {
                    $this->customer_id = $quote->customer_id;
                    $taxesCol = collect($this->taxes);
                    $this->variants = $quote->variants->map(function ($variant) use ($taxesCol) {
                        $pivotRate = (float) ($variant->pivot->tax_rate ?? 0);
                        $matched = $taxesCol->firstWhere('rate_percent', $pivotRate) ?? $taxesCol->first();
                        $rate = (float) ($matched['rate_percent'] ?? 0);
                        $inclusive = (bool) ($matched['is_price_inclusive'] ?? false);
                        $lineTotal = (float) ($variant->pivot->quantity ?? 0) * (float) ($variant->pivot->price ?? 0);
                        $base = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;
                        return [
                            'id' => $variant->id,
                            'name' => $variant->fullName,
                            'quantity' => $variant->pivot->quantity,
                            'price' => $variant->pivot->price,
                            'tax_id' => $matched['id'] ?? null,
                            'tax_rate' => $rate,
                            'tax_inclusive' => $inclusive,
                            'subtotal' => $base,
                        ];
                    })->toArray();
                    $this->total = $quote->total;
                }
            }
        }
    }


    public function updated($property, $value)
    {
        if ($property == 'quote_id') {
            $quote = Quote::find($value);
            if ($quote) {
                if ($quote->sales()->exists() || (($quote->status ?? null) === 'converted')) {
                    $this->dispatch('swal', [
                        'icon' => 'warning',
                        'title' => 'Cotización ya convertida',
                        'text' => 'Esta cotización ya tiene una venta vinculada.',
                    ]);
                    $this->quote_id = null;
                    return;
                }

                $this->customer_id = $quote->customer_id;

                $taxesCol = collect($this->taxes);
                $this->variants = $quote->variants->map(function ($variant) use ($taxesCol) {
                    $pivotRate = (float) ($variant->pivot->tax_rate ?? 0);
                    $matched = $taxesCol->firstWhere('rate_percent', $pivotRate) ?? $taxesCol->first();
                    $rate = (float) ($matched['rate_percent'] ?? 0);
                    $inclusive = (bool) ($matched['is_price_inclusive'] ?? false);
                    $lineTotal = (float) ($variant->pivot->quantity ?? 0) * (float) ($variant->pivot->price ?? 0);
                    $base = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;
                    return [
                        'id' => $variant->id,
                        'name' => $variant->product->name,
                        'quantity' => $variant->pivot->quantity,
                        'price' => $variant->pivot->price,
                        'tax_id' => $matched['id'] ?? null,
                        'tax_rate' => $rate,
                        'tax_inclusive' => $inclusive,
                        'subtotal' => $base,
                    ];
                })->toArray();
            }
        }
    }

    public function addProduct()
    {
        $this->validate([
            'variant_id' => 'required|exists:variants,id',
        ], [], [
            'variant_id' => 'producto',
        ]);

        $existing = collect($this->variants)->firstWhere('id', $this->variant_id);

        if ($existing) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'El producto ya fue agregado',
                'text' => 'El producto ya se encuentra en la fila',
            ]);
            return;
        }

        $variant = Variant::with('product')->find($this->variant_id);

        $tax = $this->default_tax_id ? Tax::find($this->default_tax_id) : null;
        $rate = (float) optional($tax)->rate_percent ?? 0;
        $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;
        $lineTotal = 1 * ((float) ($variant->price ?? 0));
        $base = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;

        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => $variant->price,
            'tax_id' => $this->default_tax_id,
            'tax_rate' => $rate,
            'tax_inclusive' => $inclusive,
            'subtotal' => $base,
        ];
        $this->reset('variant_id');
    }

    public function scanBarcode($code = null)
    {
        $code = trim((string)($code ?? ''));
        if ($code === '') {
            return;
        }

        $variant = Variant::where('barcode', $code)
            ->orWhere('sku', $code)
            ->first();

        if (!$variant) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Producto no encontrado',
                'text' => 'No existe un producto con ese código o SKU.',
            ]);
            return;
        }

        $index = collect($this->variants)->search(fn($v) => ($v['id'] ?? null) === $variant->id);
        if ($index !== false) {
            $current = $this->variants[$index];
            $current['quantity'] = (int)($current['quantity'] ?? 0) + 1;
            $rate = (float) ($current['tax_rate'] ?? 0);
            $inclusive = (bool) ($current['tax_inclusive'] ?? false);
            $lineTotal = (float)($current['quantity'] ?? 0) * (float)($current['price'] ?? 0);
            $current['subtotal'] = ($inclusive && $rate > 0)
                ? ($lineTotal / (1 + ($rate / 100)))
                : $lineTotal;
            $this->variants[$index] = $current;
            return;
        }

        $this->variant_id = $variant->id;
        $this->addProduct();
        $this->reset('variant_id');
    }

    public function updatedJournalId()
    {
        $this->updatePreview();
    }

    protected function updatePreview()
    {
        if (!$this->journal_id) {
            $this->correlative = '';
            return;
        }

        $journal = collect($this->journals)->first(function ($j) {
            if (is_array($j)) {
                return ($j['id'] ?? null) == $this->journal_id;
            }
            return ($j->id ?? null) == $this->journal_id;
        });

        // Obtener datos de secuencia según sea array u objeto
        if (is_array($journal)) {
            $sequence = $journal['sequence'] ?? null;
            $next = $sequence['next_number'] ?? null;
            $size = $sequence['sequence_size'] ?? null;
        } else {
            $sequence = $journal ? $journal->sequence : null;
            $next = $sequence ? $sequence->next_number : null;
            $size = $sequence ? $sequence->sequence_size : null;
        }

        if (!$next || !$size) {
            $this->correlative = '';
            return;
        }

        // Previsualizar correlativo sin consumir la secuencia
        $this->correlative = str_pad((string)$next, (int)$size, '0', STR_PAD_LEFT);
    }

    public function save()
    {
        $this->validate(
            [
                'journal_id' => 'required|exists:journals,id',
                'date' => 'nullable|date',
                'quote_id' => 'nullable|exists:quotes,id',
                'customer_id' => 'required|exists:customers,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'total' => 'required|numeric|min:0',
                'observation' => 'nullable|string|max:255',
                'variants' => 'required|array|min:1',
                'variants.*.id' => 'required|exists:variants,id',
                'variants.*.quantity' => 'required|numeric|min:1',
                'variants.*.price' => 'required|numeric|min:0',
                'variants.*.tax_id' => 'required|exists:taxes,id',
            ],
            [],
            [
                'journal_id' => 'serie',
                'customer_id' => 'cliente',
                'observation' => 'observación',
                'variants.*.id' => 'producto',
                'variants.*.quantity' => 'cantidad',
                'variants.*.price' => 'precio',
                'variants.*.tax_id' => 'impuesto',
            ]
        );

        $activeCompanyId = session('active_company_id');

        if (!$activeCompanyId) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear una venta.',
            ]);
            return redirect()->back();
        }

        // Calcular total en backend con impuestos dinámicos (incluyendo precio con impuesto)
        $totalCalculado = 0;
        foreach ($this->variants as $variant) {
            $tax = Tax::find($variant['tax_id']);
            $rate = (float) optional($tax)->rate_percent ?? 0;
            $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;
            $lineTotal = (float)($variant['quantity'] ?? 0) * (float)($variant['price'] ?? 0);
            $base = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;
            $totalCalculado += $base * (1 + ($rate / 100));
        }

        $parts = app(SequenceService::class)->getNextParts($this->journal_id);

        $sale = Sale::create([
            'serie' => $parts['serie'],
            'correlative' => $parts['correlative'],
            'date' => $this->date ?? now(),
            'quote_id' => $this->quote_id,
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
            'total' => $totalCalculado,
            'observation' => $this->observation,
            'company_id' => $activeCompanyId,
            'journal_id' => $this->journal_id,
        ]);

        foreach ($this->variants as $variant) {
            $tax = Tax::find($variant['tax_id']);
            $rate = (float) optional($tax)->rate_percent ?? 0;
            $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;
            $lineSubtotal = (float)($variant['quantity'] ?? 0) * (float)($variant['price'] ?? 0);
            $base = ($inclusive && $rate > 0) ? ($lineSubtotal / (1 + ($rate / 100))) : $lineSubtotal;

            $sale->variants()->attach($variant['id'], [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'tax_rate' => $rate,
                'subtotal' => $base,
            ]);

            //Kardex
            Kardex::registerExit($sale, $variant, $this->warehouse_id, 'Venta');
        }

        if ($this->quote_id) {
            $quote = Quote::find($this->quote_id);
            if ($quote && ($quote->sales()->exists() || (($quote->status ?? null) === 'converted'))) {
                session()->flash('swalt', [
                    'icon' => 'error',
                    'title' => 'Cotización convertida',
                    'text' => 'Ya existe una venta vinculada a esta cotización. No se puede crear otra.',
                ]);
                return redirect()->back();
            }
        }

        // tras crear la venta y registrar salidas de inventario
        if ($this->quote_id && isset($quote) && $quote) {
            $quote->update(['status' => 'converted']);
        }

        // Envío a SUNAT ahora se realiza mediante Job disparado en el evento created del modelo Sale

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Venta creada',
            'text' => 'El documento de venta fue creado correctamente.',
        ]);

        return redirect()->route('admin.sales.index');
    }

    public function render()
    {
        return view('livewire.admin.sales.sale-create');
    }
}
