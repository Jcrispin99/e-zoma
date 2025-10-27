<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Departamento</label>
        <select name="department_id" wire:model.live="department_id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Seleccione un departamento</option>
            @foreach ($departments as $d)
                <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Provincia</label>
        <select name="province_id" wire:model.live="province_id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Seleccione una provincia</option>
            @foreach ($provinces as $p)
                <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Distrito</label>
        <select name="district_id" wire:model.live="district_id" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Seleccione un distrito</option>
            @foreach ($districts as $di)
                <option value="{{ $di['id'] }}">{{ $di['name'] }}</option>
            @endforeach
        </select>
    </div>
</div>