<?php

namespace App\Livewire\Admin\Datatables;

use App\Mail\PdfSend;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Transfer;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Illuminate\Support\Facades\Mail;

class TransferTable extends DataTableComponent
{
    protected $model = Transfer::class;

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
            Column::make("Origen", "originWarehouse.name")
                ->sortable(),
            Column::make("Destino", "destinationWarehouse.name")
                ->sortable(),
            Column::make("Total", "total")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.transfers.actions', ['transfer' => $row]);
                })
        ];
    }

    public function builder(): Builder
    {
        return Transfer::query()->with(['originWarehouse', 'destinationWarehouse']);
    }
    //Propiedades
    public $form = [
        'open' => false,
        'document' => '',
        'client' => '',
        'email' => '',
        'model' => null,
        'view_pdf_patch' => 'admin.quotes.pdf',
    ];

    //Metodos
    public function openModal(Transfer $transfer)
    {
        $this->form['open'] = true;
        $this->form['document'] = 'Transferencia ' . ' ' . $transfer->serie . ' ' . $transfer->correlative;
        $this->form['client'] =  $transfer->originWarehouse->name;
        $this->form['email'] = '';
        $this->form['model'] = $transfer;
        $this->form['view_pdf_patch'] = 'admin.transfers.pdf';
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
