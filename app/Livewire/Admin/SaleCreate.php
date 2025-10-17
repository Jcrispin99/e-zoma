<?php

namespace App\Livewire\Admin;

use App\Facades\Kardex;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\Variant;
use Livewire\Component;
use App\Models\Quote;
use App\Models\Journal;
use App\Services\SequenceService;
use App\Services\KardexServices;

class SaleCreate extends Component
{
    public $voucher_type = 1;
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

    public $journals = [];
    public $journal_id;

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

        // Cargar datos desde cotización si viene en la URL
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
                    $this->voucher_type = $quote->voucher_type;
                    $this->customer_id = $quote->customer_id;
                    $this->variants = $quote->variants->map(function ($variant) {
                        return [
                            'id' => $variant->id,
                            'name' => $variant->fullName,
                            'quantity' => $variant->pivot->quantity,
                            'price' => $variant->pivot->price,
                            'subtotal' => $variant->pivot->subtotal,
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

                $this->voucher_type = $quote->voucher_type;
                $this->customer_id = $quote->customer_id;

                $this->variants = $quote->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->product->name,
                        'quantity' => $variant->pivot->quantity,
                        'price' => $variant->pivot->price,
                        'subtotal' => $variant->pivot->subtotal,
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

        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => $variant->price,
            'subtotal' => $variant->price,
        ];
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
                'voucher_type' => 'required|in:1,2',
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
            ],
            [],
            [
                'voucher_type' => 'tipo de comprobante',
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
                'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear una venta.',
            ]);
            return redirect()->back();
        }

        // Obtener serie y correlativo con consumo de secuencia
        $parts = app(SequenceService::class)->getNextParts($this->journal_id);

        $sale = Sale::create([
            'voucher_type' => $this->voucher_type,
            'serie' => $parts['serie'],
            'correlative' => $parts['correlative'],
            'date' => $this->date ?? now(),
            'quote_id' => $this->quote_id,
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
            'total' => $this->total,
            'observation' => $this->observation,
            'company_id' => $activeCompanyId,
            'journal_id' => $this->journal_id,
        ]);

        foreach ($this->variants as $variant) {
            $sale->variants()->attach($variant['id'], [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'subtotal' => $variant['quantity'] * $variant['price'],
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

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Venta creada',
            'text' => 'El documento de venta fue creado correctamente.',
        ]);

        return redirect()->route('admin.sales.index');
    }

    public function render()
    {
        return view('livewire.admin.sale-create');
    }
}
