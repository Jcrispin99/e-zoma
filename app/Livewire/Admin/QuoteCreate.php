<?php

namespace App\Livewire\Admin;

use App\Models\Variant;
use Livewire\Component;
use App\Models\Quote;
use App\Services\SequenceService;
use App\Models\Journal;

class QuoteCreate extends Component
{
    public $voucher_type = 1;

    public $date;
    public $customer_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];

    public $journals;
    public $journal_id;
    public $correlative;

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
        $this->variants = [];

        $activeCompanyId = session('active_company_id');

        $this->journals = Journal::where('type', 'quote')
            ->where('company_id', $activeCompanyId)
            ->get();

        if ($this->journals->isNotEmpty()) {
            $this->journal_id = $this->journals->first()->id;
            $this->getCorrelative();
        }
    }

    public function updatedJournalId()
    {
        $this->getCorrelative();
    }

    public function getCorrelative()
    {
        $journal = Journal::with('sequence')->find($this->journal_id);
        $sequence = $journal->sequence;
        $this->correlative = str_pad($sequence->next_number, $sequence->sequence_size, '0', STR_PAD_LEFT);
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

    public function save()
    {
        $this->validate(
            [
                'voucher_type' => 'required|in:1,2',
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
                'voucher_type' => 'tipo de comprobante',
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
            'voucher_type' => $this->voucher_type,
            'serie' => $sequenceData['serie'],
            'correlative' => $sequenceData['correlative'],
            'date' => $this->date ?? now(),
            'customer_id' => $this->customer_id,
            'total' => $this->total,
            'observation' => $this->observation,
            'company_id' => $activeCompanyId,
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
        return view('livewire.admin.quote-create');
    }
}
