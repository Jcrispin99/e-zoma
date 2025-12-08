<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KardexExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $inventories;

    public function __construct($inventories)
    {
        $this->inventories = $inventories;
    }

    public function collection()
    {
        return $this->inventories;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Almacén',
            'Producto',
            'Variante',
            'Detalle',
            'Entrada',
            'Salida',
            'Saldo'
        ];
    }

    public function map($inventory): array
    {
        return [
            $inventory->created_at->format('d/m/Y H:i'),
            $inventory->warehouse->name ?? '-',
            $inventory->variant->product->name ?? 'Desconocido',
            $inventory->variant->attributeValues->pluck('value')->join(' / ') ?: 'Principal',
            $inventory->detail,
            $inventory->quantity_in ?? 0,
            $inventory->quantity_out ?? 0,
            $inventory->quantity_balance
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
