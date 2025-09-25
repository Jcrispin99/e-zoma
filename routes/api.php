<?php

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\Variant;
use App\Models\Quote;
use App\Models\Reason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Attribute;
use App\Models\AttributeValue;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/suppliers', function (Request $request) {
    return Supplier::select('id', 'name')
        ->when($request->search, function ($query, $search) {
            $query->where('name', 'like', "{$search}")
                ->orWhere('document_number', 'like', "{$search}");
        })
        ->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )->get();
})->name('api.suppliers.index');

Route::post('/customers', function (Request $request) {
    return Customer::select('id', 'name')
        ->when($request->search, function ($query, $search) {
            $query->where('name', 'like', "{$search}")
                ->orWhere('document_number', 'like', "{$search}");
        })
        ->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )->get();
})->name('api.customers.index');

Route::post('/warehouses', function (Request $request) {
    return Warehouse::select('id', 'name', 'location as description')
        ->when($request->search, function ($query, $search) {
            $query->where('name', 'like', "{$search}")
                ->orWhere('location', 'like', "{$search}");
        })
        ->when($request->exclude, function ($query, $exclude) {
            $query->where('id', '!=', $exclude);
        })
        ->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )->get();
})->name('api.warehouse.index');

Route::post('/product', function (Request $request) {
    return Variant::query()
        ->select('variants.id', 'products.name as name')
        ->join('products', 'products.id', '=', 'variants.product_id')
        ->when($request->search, function ($query, $search) {
            $query->where('products.name', 'like', "%{$search}%")
                ->orWhere('variants.sku', 'like', "%{$search}%");
        })
        ->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('variants.id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )->get();
})->name('api.product.index');

Route::post('/purchase-orders', function (Request $request) {
    $purchaseOrders = PurchaseOrder::when($request->search, function ($query, $search) {

        //OC-123
        $parts = explode('-', $search);

        if (count($parts) == 1) {
            //buscar por nombre de proveedor
            $query->whereHas('supplier', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
            });
            return;
        }

        if (count($parts) == 2) {

            $serie = $parts[0];
            $correlative = ltrim($parts[1], '0');

            $query->where('serie', $serie)
                ->where('correlative', 'LIKE', "%{$correlative}%");
            return;
        }
    })
        ->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )
        ->with(['supplier'])
        ->orderBy('created_at', 'desc')
        ->get();

    return $purchaseOrders->map(function ($purchaseOrder) {
        return [
            'id' => $purchaseOrder->id,
            'name' => $purchaseOrder->serie . '-' . $purchaseOrder->correlative,
            'description' => $purchaseOrder->supplier->name . ' - ' . $purchaseOrder->supplier->document_number,
        ];
    });
})->name('api.purchase-orders.index');

Route::post('/quotes', function (Request $request) {
    $quotes = Quote::when($request->search, function ($query, $search) {

        //OC-123
        $parts = explode('-', $search);

        if (count($parts) == 1) {
            //buscar por nombre de proveedor
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
            });
            return;
        }

        if (count($parts) == 2) {

            $serie = $parts[0];
            $correlative = ltrim($parts[1], '0');

            $query->where('serie', $serie)
                ->where('correlative', 'LIKE', "%{$correlative}%");
            return;
        }
    })
        ->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )
        ->with(['customer'])
        ->orderBy('created_at', 'desc')
        ->get();

    return $quotes->map(function ($quote) {
        return [
            'id' => $quote->id,
            'name' => $quote->serie . '-' . $quote->correlative,
            'description' => $quote->customer->name . ' - ' . $quote->customer->document_number,
        ];
    });
})->name('api.quotes.index');

Route::post('/reasons', function (Request $request) {
    return Reason::select('id', 'name')
        ->when($request->search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })
        ->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )
        ->where('type', $request->input('type', ''))
        ->get();
})->name('api.reasons.index');

Route::post('/attributes', function (Request $request) {
    return Attribute::select('id', 'name')
        ->when($request->search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })
        ->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )
        ->get();
})->name('api.attributes.index');

// Ruta para obtener valores de un atributo específico
Route::post('/attribute-values/{attributeId}', function (Request $request, $attributeId) {
    return AttributeValue::select('id', 'value', 'attribute_id')
        ->where('attribute_id', $attributeId)
        ->when($request->search, function ($query, $search) {
            $query->where('value', 'like', "%{$search}%");
        })
        ->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(20)
        )
        ->get();
})->name('api.attribute-values.show');
