<x-filament-panels::page>
    <div class="max-w-xs">
        <label for="month" class="text-sm font-medium">Mes</label>
        <input
            id="month"
            type="month"
            wire:model.live="month"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm dark:border-white/10 dark:bg-white/5"
        />
    </div>

    {{ $this->table }}
</x-filament-panels::page>
