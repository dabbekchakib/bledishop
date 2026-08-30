<x-filament-widgets::widget>
    <x-filament::section :heading="__('admin.dashboard.period_filter')">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end lg:gap-4">
                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="period" :label="__('admin.dashboard.period')">
                            @foreach ($presets as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                @if ($period === 'custom')
                    <div>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="from" :label="__('admin.dashboard.date_from')" />
                        </x-filament::input.wrapper>
                    </div>

                    <div>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="to" :label="__('admin.dashboard.date_to')" />
                        </x-filament::input.wrapper>
                    </div>
                @endif
            </div>

            <x-filament::badge color="gray">
                {{ __('admin.dashboard.current_period') }}: {{ $label }}
            </x-filament::badge>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
