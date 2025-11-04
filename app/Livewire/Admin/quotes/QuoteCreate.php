<?php

namespace App\Livewire\Admin\quotes;

use App\Livewire\Concerns\WithTaxes;
use App\Models\Variant;
use Livewire\Component;
use App\Models\Quote;
use App\Services\SequenceService;
use App\Models\Journal;
use App\Models\Tax;

class QuoteCreate extends Component
{

    public $date;
    public $customer_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];
    public $taxes = [];
    public $default_tax_id = null;


    public $correlative;
    public $journals = [];
    public $journal_id;

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
        $this->variants = [];

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

        // 1. Cargar journals de tipo 'quote' con sus secuencias para evitar N+1
        $this->journals = Journal::where('type', 'quote')
            ->with('sequence')
            ->get();

        // 2. Establecer el primer journal como predeterminado y actualizar la vista previa
        $journalsCol = collect($this->journals);
        if ($journalsCol->isNotEmpty()) {
            $first = $journalsCol->first();
            $this->journal_id = is_array($first) ? ($first['id'] ?? null) : ($first->id ?? null);
            $this->updatePreview();
        }
    }

    public function updatedJournalId()
    {
        $this->updatePreview();
    }

    // 3. Renombrado a updatePreview y optimizado para no hacer más queries a la BD
    public function updatePreview()
    {
        $journal = collect($this->journals)->first(function ($j) {
            if (is_array($j)) {
                return ($j['id'] ?? null) == $this->journal_id;
            }
            return ($j->id ?? null) == $this->journal_id;
        });

        if (is_array($journal)) {
            $sequence = $journal['sequence'] ?? null;
            $next = $sequence['next_number'] ?? null;
            $size = $sequence['sequence_size'] ?? null;
        } else {
            $sequence = $journal ? $journal->sequence : null;
            $next = $sequence ? $sequence->next_number : null;
            $size = $sequence ? $sequence->sequence_size : null;
        }

        $this->correlative = ($next && $size)
            ? str_pad((string)$next, (int)$size, '0', STR_PAD_LEFT)
            : '';
    }

    public function boot()
    {
        //Verificar si hay errores de validación previos
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

        // Impuesto por defecto
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

    public function save()
    {
        $this->validate(
            [
                'journal_id' => 'required|exists:journals,id',
                'date' => 'nullable|date',
                'customer_id' => 'required|exists:customers,id',
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
                'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear una compra.',
            ]);
            return redirect()->back();
        }

        // Calcular total con impuestos dinámicos (incluyendo precios TTC si aplica)
        $totalCalculado = 0;
        foreach ($this->variants as $variant) {
            $tax = Tax::find($variant['tax_id']);
            $rate = (float) optional($tax)->rate_percent ?? 0;
            $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;
            $lineTotal = ($variant['quantity'] ?? 0) * ($variant['price'] ?? 0);
            $base = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;
            $taxAmount = $base * ($rate / 100);
            $totalCalculado += $base + $taxAmount;
        }

        $sequenceData = app(SequenceService::class)->getNextParts($this->journal_id);

        $quote = Quote::create([
            'journal_id' => $this->journal_id, // 4. Guardar el journal_id
            'serie' => $sequenceData['serie'],
            'correlative' => $sequenceData['correlative'],
            'date' => $this->date ?? now(),
            'customer_id' => $this->customer_id,
            'total' => $totalCalculado,
            'observation' => $this->observation,
            'company_id' => $activeCompanyId,
            'status' => 'draft',
        ]);

        foreach ($this->variants as $variant) {
            $tax = Tax::find($variant['tax_id']);
            $rate = (float) optional($tax)->rate_percent ?? 0;
            $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;
            $lineTotal = ($variant['quantity'] ?? 0) * ($variant['price'] ?? 0);
            $base = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;

            $quote->variants()->attach($variant['id'], [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'tax_rate' => $rate,
                'subtotal' => $base,
            ]);
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Cotización creada exitosamente.',
        ]);

        return redirect()->route('admin.quotes.index');
    }

    public function render()
    {
        return view('livewire.admin.quotes.quote-create');
    }
}
