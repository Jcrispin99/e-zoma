<?php

namespace App\Livewire\Admin\Datatables;

use App\Mail\PdfSend;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Quote;
use Illuminate\Support\Facades\Mail;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use App\Livewire\Traits\CompanyFilterable;

class QuoteTable extends DataTableComponent
{
    use CompanyFilterable;

    protected $listeners = ['company-changed' => '$refresh'];

    protected $model = Quote::class;

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
            Column::make("Voucher type", "voucher_type")
                ->sortable(),
            Column::make("Serie", "serie")
                ->sortable(),
            Column::make("Correlative", "correlative")
                ->sortable(),
            Column::make("Date", "date")
                ->sortable()
                ->format(fn($value) => $value->format('Y/m/d')),
            Column::make("Customer id", "customer.name")
                ->sortable(),
            Column::make("Total", "total")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.quotes.actions', ['quote' => $row]);
                })
        ];
    }

    public function builder(): Builder
    {
        $query = Quote::query()->with(['customer']);
        $selectedCompanyIds = session()->get('selected_company_ids', []);
        if ($selectedCompanyIds) {
            $query->whereIn('company_id', $selectedCompanyIds);
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
    public function openModal(Quote $quote)
    {
        $this->form['open'] = true;
        $this->form['document'] = 'Cotización ' . ' ' . $quote->serie . ' ' . $quote->correlative;
        $this->form['client'] =  $quote->customer->document_number . ' - ' . $quote->customer->name;
        $this->form['email'] = $quote->customer->email;
        $this->form['model'] = $quote;
        $this->form['view_pdf_patch'] = 'admin.quotes.pdf';
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
