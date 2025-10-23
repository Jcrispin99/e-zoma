<form wire:submit.prevent="save" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-wire-input label="Nombre" wire:model="name" required />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-wire-native-select label="Compañía" wire:model="company_id">
            <option value="">—</option>
            @foreach ($companies as $company)
            <option value="{{ $company->id }}">{{ $company->name }}</option>
            @endforeach
        </x-wire-native-select>
        <x-wire-native-select label="Tipo" wire:model="program_type" required>
            <option value="loyalty">Lealtad</option>
            <option value="promotion">Promoción</option>
            <option value="promo_code">Código promocional</option>
            <option value="coupons">Cupones</option>
            <option value="buy_x_get_y">Buy X Get Y</option>
            <option value="gift_card">Gift Card</option>
            <option value="next_order_coupons">Cupones próxima orden</option>
        </x-wire-native-select>
        <x-wire-native-select label="Aplicación" wire:model="applies_on">
            <option value="current">Actual</option>
            <option value="future">Futura</option>
            <option value="both">Ambas</option>
            </x-wire-native_select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-wire-native-select label="Disparo" wire:model="trigger">
            <option value="auto">Automático</option>
            <option value="with_code">Con código</option>
        </x-wire-native-select>
        <x-wire-input type="date" label="Desde" wire:model="date_from" />
        <x-wire-input type="date" label="Hasta" wire:model="date_to" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-wire-checkbox label="Activo" wire:model="active" />
        <x-wire-checkbox label="Venta" wire:model="sale_ok" />
        <x-wire-checkbox label="E-commerce" wire:model="ecommerce_ok" />
        <x-wire-checkbox label="POS" wire:model="pos_ok" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-wire-checkbox label="Limitar uso" wire:model="limit_usage" />
        <x-wire-input type="number" label="Máximo uso" wire:model="max_usage" min="1" />
        <x-wire-input type="number" label="Website ID" wire:model="website_id" />
    </div>

    <div class="flex justify-end">
        <x-wire-button type="submit" green>
            Guardar
        </x-wire-button>
    </div>
</form>
