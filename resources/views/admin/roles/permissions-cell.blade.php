<div class="max-w-[360px] overflow-hidden">
    <div class="flex flex-wrap gap-1">
        @forelse ($role->permissions as $perm)
        <span
            class="inline-flex px-2 py-0.5 text-xs rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 whitespace-normal break-words">
            {{ $perm->name }}
        </span>
        @empty
        <span class="text-xs text-gray-500 dark:text-gray-400">Sin permisos</span>
        @endforelse
    </div>
</div>