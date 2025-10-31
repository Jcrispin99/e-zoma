<?php

namespace App\Livewire\Admin\Imports;

use Livewire\Component;
use App\Exports\ProductsTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class ProductImport extends Component
{

    public function downloadTemplate()
    {
        return Excel::download(new ProductsTemplateExport(), 'product_template.xlsx');
    }

    public function render()
    {
        return view('livewire.admin.imports.product-import');
    }
}
