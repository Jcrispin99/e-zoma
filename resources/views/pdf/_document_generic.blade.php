@php($n = 1)
@php($total = 0)
@php($m = $model ?? ($sale ?? null) ?? ($purchase ?? null) ?? ($quote ?? null) ?? ($po ?? null))
@php(
  $docLabel = $documentLabel ?? (
    $m instanceof \App\Models\Sale ? 'Venta' : (
      $m instanceof \App\Models\Purchase ? 'Compra' : (
        $m instanceof \App\Models\PurchaseOrder ? 'OC' : (
          $m instanceof \App\Models\Quote ? 'Cotización' : 'Documento'
        )
      )
    )
  )
)
@php($subject = $m->customer ?? $m->supplier ?? null)
@php($subjectLabel = ($m->customer ?? null) ? 'Cliente' : (($m->supplier ?? null) ? 'Proveedor' : 'Cliente'))

<div class="doc">
  <div class="doc-header">
    <div class="doc-title">{{ $docLabel }} #{{ $m->serie }}-{{ str_pad($m->correlative, 4, '0', STR_PAD_LEFT) }}</div>
    <div class="doc-meta">Fecha: {{ optional($m->date)->format('d/m/Y') }}</div>
  </div>

  <table class="info-table">
    <tr>
      <td style="width:50%; padding-right:12px;">
        <div class="block-title">Empresa</div>
        <div class="block-content">
          <div>{{ optional(optional($m->journal)->company)->name }}</div>
          @if(optional(optional($m->journal)->company)->document_number)
            <div>RUC: {{ optional(optional($m->journal)->company)->document_number }}</div>
          @endif
          @if(optional(optional($m->journal)->company)->address)
            <div>Dirección: {{ optional(optional($m->journal)->company)->address }}</div>
          @endif
          @if(optional(optional($m->journal)->company)->email)
            <div>Correo: {{ optional(optional($m->journal)->company)->email }}</div>
          @endif
          @if(optional(optional($m->journal)->company)->phone)
            <div>Teléfono: {{ optional(optional($m->journal)->company)->phone }}</div>
          @endif
        </div>
      </td>
      <td style="width:50%; padding-left:12px;">
        <div class="block-title">{{ $subjectLabel }}</div>
        <div class="block-content">
          <div>{{ optional($subject)->name }}</div>
          @if(optional($subject)->document_number)
            <div>Documento: {{ optional($subject)->document_number }}</div>
          @endif
          @if(optional($subject)->address)
            <div>Dirección: {{ optional($subject)->address }}</div>
          @endif
          @if(optional($subject)->email)
            <div>Correo: {{ optional($subject)->email }}</div>
          @endif
          @if(optional($subject)->phone)
            <div>Teléfono: {{ optional($subject)->phone }}</div>
          @endif
          @if(optional($m->warehouse)->name)
            <div>Almacén: {{ optional($m->warehouse)->name }}</div>
          @endif
        </div>
      </td>
    </tr>
  </table>

  <div style="margin-top: 12px;">
    <div class="block-title">Detalle de productos</div>
    <table class="items-table">
      <thead>
        <tr>
          <th style="width: 3rem;">#</th>
          <th>Producto</th>
          <th style="width: 6rem;">Cantidad</th>
          <th style="width: 8rem;">Precio Unitario</th>
          <th style="width: 6rem;">IGV</th>
          <th style="width: 8rem;">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @foreach($m->variants as $variant)
          @php($qty = (float) ($variant->pivot->quantity ?? 0))
          @php($price = (float) ($variant->pivot->price ?? 0))
          @php($taxRate = (float) ($variant->pivot->tax_rate ?? 0))
          @php($subtotal = (float) ($variant->pivot->subtotal ?? ($qty * $price)))
          @php($total += $subtotal)
          <tr>
            <td>{{ $n++ }}</td>
            <td>{{ $variant->fullName ?? $variant->name }}</td>
            <td>{{ number_format($qty, 2) }}</td>
            <td>{{ number_format($price, 2) }}</td>
            <td>{{ number_format($taxRate, 2) }}%</td>
            <td>{{ number_format($subtotal, 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="totals">
    <div class="totals-box">
      <div class="muted">Observación</div>
      <div>{{ $m->observation ?? '—' }}</div>
      <div style="margin-top: 6px; font-weight: 600;">Total: S/ {{ number_format(($m->total ?? null) !== null ? $m->total : $total, 2) }}</div>
    </div>
  </div>

  <div class="footer-muted">Documento generado por e-zoma</div>
</div>