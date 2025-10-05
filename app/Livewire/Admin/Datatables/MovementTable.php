<?php

namespace App\Livewire\Admin\Datatables;

use App\Mail\PdfSend;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Movement;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Illuminate\Support\Facades\Mail;
use App\Livewire\Traits\CompanyFilterable;

class MovementTable extends DataTableComponent
{
    //protected $model = Movement::class;

    use CompanyFilterable;

    protected $listeners = ['company-changed' => '$refresh'];

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
            Column::make("Tipo", "type")
                ->sortable()
                ->format(fn($value) => $value == 1 ? 'Entrada' : 'Salida'),
            Column::make("Serie", "serie")
                ->sortable(),
            Column::make("Correlative", "correlative")
                ->sortable(),
            Column::make("Almacen", "warehouse.name")
                ->sortable(),
            Column::make("Motivo", "reason.name")
                ->sortable(),
            Column::make("Total", "total")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.movements.actions', ['movement' => $row]);
                })
        ];
    }

    public function builder(): Builder
    {
        $query = Movement::query()->with(['warehouse', 'reason']);
        $selectedCompanyIds = session()->get('selected_company_ids', []);

        if (!empty($selectedCompanyIds)) {
            $query->whereIn('movements.company_id', $selectedCompanyIds);
        }

        return $query;
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
    public function openModal(Movement $movement)
    {
        $this->form['open'] = true;
        $this->form['document'] = 'Movimiento ' . ' ' . $movement->serie . ' ' . $movement->correlative;
        $this->form['client'] =  $movement->warehouse->name;
        $this->form['email'] = '';
        $this->form['model'] = $movement;
        $this->form['view_pdf_patch'] = 'admin.movements.pdf';
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
