{{-- resources/views/reports/purchase-detail.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Compra</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 20px; }
        .title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .section { margin-top: 20px; }
    </style>
</head>
<body>

    <div class="title">Detalle de Compra #{{ $model->serie }}-{{ str_pad($model->correlative, 4, '0', STR_PAD_LEFT) }}</div>

    <div>
        <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($model->date)->format('d/m/Y') }}<br>
        <strong>Proveedor:</strong> {{ $model->supplier->name ?? '—' }}<br>
        <strong>Almacén:</strong> {{ $model->warehouse->name ?? '—' }}<br>
        <strong>Observación:</strong> {{ $model->observation ?? '—' }}
    </div>

    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($model->variants as $i => $variant)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $variant->product->name . ($variant->formatted_attributes ? ' (' . $variant->formatted_attributes . ')' : '') }}</td>
                        <td>{{ $variant->pivot->quantity }}</td>
                        <td>S/ {{ number_format($variant->pivot->price, 2) }}</td>
                        <td>S/ {{ number_format($variant->pivot->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section" style="text-align: right;">
        <strong>Total: S/ {{ number_format($model->total, 2) }}</strong>
    </div>

</body>
</html>