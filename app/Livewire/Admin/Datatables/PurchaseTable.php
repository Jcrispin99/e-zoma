<?php

namespace App\Livewire\Admin\Datatables;

use App\Mail\PdfSend;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;

class PurchaseTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');

        $this->setConfigurableAreas(
            [
                'after-wrapper' => [
                    'admin.mail.modal',
                ],
            ]
        );
    }

    public function filters(): array
    {
        return [
            DateRangeFilter::make('Fecha')
                ->config([
                    'placeholder' => 'Selecionar rango',
                ])
                ->filter(function ($query, array $dateRange) {
                    $query->whereBetween('date', [
                        $dateRange['minDate'] ?? now()->startOfMonth(),
                        $dateRange['maxDate'] ?? now()->endOfMonth(),
                    ]);
                }),
            MultiSelectFilter::make('Proveedores')
                ->options(
                    Supplier::query()
                        ->orderBy('name')
                        ->get()
                        ->keyBy('id')
                        ->map(fn($tag) => $tag->name)
                        ->toArray()
                )
                ->filter(function ($query, array $selected) {
                    $query->whereIn('supplier_id', $selected);
                }),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Voucher type", "voucher_type")
                ->sortable(),
            Column::make("Serie", "serie")
                ->sortable(),
            Column::make("Correlative", "correlative")
                ->sortable(),
            Column::make("Date", "date")
                ->sortable()
                ->format(fn($value) => $value->format('Y/m/d')),
            Column::make("Purchase order id", "purchase_order_id")
                ->sortable(),
            Column::make("Supplier id", "supplier.name")
                ->sortable(),
            Column::make("Warehouse id", "warehouse.name")
                ->sortable(),
            Column::make("Total", "total")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.purchases.actions', ['purchase' => $row]);
                })
        ];
    }
    public function builder(): Builder
    {
        return Purchase::query()->with(['supplier', 'warehouse']);
    }

    //Propiedades
    public $form = [
        'open' => false,
        'document' => '',
        'client' => '',
        'email' => '',
        'model' => null,
        'view_pdf_patch' => 'admin.purchases.pdf',
    ];

    //Metodos
    public function openModal(Purchase $purchase)
    {
        $this->form['open'] = true;
        $this->form['document'] = 'Compra ' . ' ' . $purchase->serie . ' ' . $purchase->correlative;
        $this->form['client'] =  $purchase->supplier->document_number . ' - ' . $purchase->supplier->name;
        $this->form['email'] = $purchase->supplier->email;
        $this->form['model'] = $purchase;
    }

    public function sendEmail()
    {
        $this->validate(
            [
                'form.email' => 'required|email',
            ]
        );

        //Llamar a un mailable
        Mail::to($this->form['email'])
            ->send(new PdfSend($this->form));
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'El email ha sido enviado correctamente',
        ]);
        $this->reset('form');
    }
}
