<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use App\Mail\PdfSend;
use Illuminate\Support\Facades\Mail;
use App\Livewire\Traits\CompanyFilterable;


class PurchaseOrderTable extends DataTableComponent
{
    use CompanyFilterable;

    protected $listeners = ['company-changed' => '$refresh'];

    // protected $model = PurchaseOrder::class;

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
                })
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Date", "date")
                ->sortable()
                ->format(fn($value) => $value->format('Y/m/d')),

            Column::make("Serie", "serie")
                ->sortable(),
            Column::make("Correlative", "correlative")
                ->sortable(),

            Column::make("Documento", "supplier.document_number")
                ->sortable(),
            Column::make("Razon social", "supplier.name")
                ->sortable(),

            Column::make("Total", "total")
                ->sortable()
                ->format(fn($value) => number_format($value, 2, ',', '.')),

            Column::make("Acciones")
                ->label(fn($row) => view('admin.purchases-orders.actions', [
                    'purchaseOrder' => $row,
                ])),
        ];
    }
    public function builder(): Builder
    {
        return $this->applyCompanyFilter(PurchaseOrder::query()->with(['supplier']));
    }

    //Propiedades
    public $form = [
        'open' => false,
        'document' => '',
        'client' => '',
        'email' => '',
        'model' => null,
        'view_pdf_patch' => 'admin.purchases-orders.pdf',
    ];

    //Metodos
    public function openModal(PurchaseOrder $purchaseOrder)
    {
        $this->form['open'] = true;
        $this->form['document'] = 'Orden de Compra ' . ' ' . $purchaseOrder->serie . ' ' . $purchaseOrder->correlative;
        $this->form['client'] =  $purchaseOrder->supplier->document_number . ' - ' . $purchaseOrder->supplier->name;
        $this->form['email'] = $purchaseOrder->supplier->email;
        $this->form['model'] = $purchaseOrder;
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
