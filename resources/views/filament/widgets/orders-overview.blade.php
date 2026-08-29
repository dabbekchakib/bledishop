<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ __('Aperçu des commandes') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $stats['period_label'] }}
                </p>
            </div>

            <select
                wire:model.live="period"
                class="rounded-lg border-gray-300 bg-white text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                aria-label="{{ __('Période') }}"
            >
                <option value="today">Aujourd'hui</option>
                <option value="week">Cette semaine</option>
                <option value="month" selected>Ce mois</option>
                <option value="year">Cette année</option>
                <option value="all">Tout</option>
            </select>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Commandes') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $stats['orders'] }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Chiffre d\'affaires') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $stats['revenue'] }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Commandes en attente') }}</p>
                <p class="mt-1 text-2xl font-bold text-amber-600">{{ $stats['pending'] }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Stock faible') }}</p>
                <p class="mt-1 text-2xl font-bold text-red-600">{{ $stats['low_stock'] }}</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
