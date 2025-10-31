<?php

namespace App\Livewire\Admin\quotes;

use App\Models\Variant;
use Livewire\Component;
use App\Models\Quote;
use App\Services\SequenceService;
use App\Models\Journal;

class QuoteCreate extends Component
{
    public $date;
    public $customer_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];

    public $correlative;
    public $journals = [];
    public $journal_id;

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
        $this->variants = [];

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

        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => $variant->price,
            'subtotal' => $variant->price,
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
            $current['subtotal'] = (float)($current['quantity'] ?? 0) * (float)($current['price'] ?? 0);
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
            ],
            [],
            [
                'journal_id' => 'serie',
                'customer_id' => 'cliente',
                'observation' => 'observación',
                'variants.*.id' => 'producto',
                'variants.*.quantity' => 'cantidad',
                'variants.*.price' => 'precio',
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

        $sequenceData = app(SequenceService::class)->getNextParts($this->journal_id);

        $quote = Quote::create([
            'journal_id' => $this->journal_id, // 4. Guardar el journal_id
            'serie' => $sequenceData['serie'],
            'correlative' => $sequenceData['correlative'],
            'date' => $this->date ?? now(),
            'customer_id' => $this->customer_id,
            'total' => $this->total,
            'observation' => $this->observation,
            'company_id' => $activeCompanyId,
            'status' => 'draft',
        ]);

        foreach ($this->variants as $variant) {
            $quote->variants()->attach($variant['id'], [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'subtotal' => $variant['quantity'] * $variant['price'],
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
