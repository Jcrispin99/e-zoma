<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ValuationExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $variants;

    public function __construct($variants)
    {
        $this->variants = $variants;
    }

    public function collection()
    {
        return $this->variants;
    }

    public function headings(): array
    {
        return [
            'Producto',
            'Variante',
            'SKU',
            'Stock',
            'Precio Unit.',
            'Valor Total'
        ];
    }

    public function map($variant): array
    {
        $valuation = $variant->stock * $variant->price;

        return [
            $variant->product->name,
            $variant->attributeValues->pluck('value')->join(' / ') ?: 'Principal',
            $variant->sku,
            $variant->stock,
            $variant->price,
            $valuation
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
