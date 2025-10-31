<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QrStyle extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'name',
        'is_default',
        'label_width',
        'label_height',
        'layout_type',
        'qr_size',
        'show_product_name',
        'show_description',
        'show_price',
        'show_sku',
        'show_barcode_text',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'show_product_name' => 'boolean',
        'show_description' => 'boolean',
        'show_price' => 'boolean',
        'show_sku' => 'boolean',
        'show_barcode_text' => 'boolean',
    ];
}
