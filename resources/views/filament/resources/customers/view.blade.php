<x-filament-panels::page>
    @php
        $customer = $this->getRecord();
        $hasOrders = $customer->orders_count > 0;
        $lastOrder = $customer->orders()->latest('created_at')->first();
    @endphp

    {{-- Customer header --}}
    <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary-100 text-lg font-bold text-primary-700 dark:bg-primary-500/20 dark:text-primary-400">
                {{ strtoupper(substr(trim((string) $customer->fullName()), 0, 1)) }}
            </div>
            <div class="min-w-0">
                <h2 class="truncate text-xl font-bold text-gray-900 dark:text-white">{{ $customer->fullName() }}</h2>
                <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                    {{ $customer->email }}{{ $customer->phone ? ' · '.$customer->phone : '' }}
                </p>
            </div>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            @if ($customer->is_active)
                <x-filament::badge color="success">{{ __('admin.customers.active') }}</x-filament::badge>
            @else
                <x-filament::badge color="danger">{{ __('admin.customers.inactive') }}</x-filament::badge>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">{{ $customer->orders_count }}</p>
            <p class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.customers.stat_orders') }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">{{ format_price(((int) $customer->orders_sum_total) / 100) }}</p>
            <p class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.customers.stat_total') }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">{{ $lastOrder ? $lastOrder->created_at->format('d/m/Y') : '-' }}</p>
            <p class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.customers.stat_last_order') }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">{{ $customer->addresses()->count() }}</p>
            <p class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.customers.stat_default_address') }}</p>
        </div>
    </div>

    {{-- Overview --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('admin.customers.overview') }}</h3>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.customers.member_since') }}</dt>
                <dd class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $customer->created_at?->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.customers.last_login') }}</dt>
                <dd class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $customer->last_login_at ? $customer->last_login_at->format('d/m/Y H:i') : __('admin.customers.no_logins') }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.customers.preferred_language') }}</dt>
                <dd class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ $customer->locale ? strtoupper($customer->locale) : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.customers.stat_email_verified') }}</dt>
                <dd class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">
                    {{ __('forms::components.boolean.'.($customer->email_verified_at ? 'true' : 'false')) }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- Recent orders --}}
    @if ($hasOrders)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('account.recent_orders') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-start text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th scope="col" class="px-6 py-3 text-start font-semibold">{{ __('admin.orders.column_number') }}</th>
                            <th scope="col" class="px-6 py-3 text-start font-semibold">{{ __('admin.orders.column_date') }}</th>
                            <th scope="col" class="px-6 py-3 text-start font-semibold">{{ __('admin.orders.column_status') }}</th>
                            <th scope="col" class="px-6 py-3 text-end font-semibold">{{ __('admin.orders.column_total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($customer->orders()->latest('created_at')->take(5)->get() as $order)
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-3 font-semibold text-primary-600 dark:text-primary-400">
                                    <a href="{{ \Filament\Facades\Filament::getDefaultPanel()->getUrl(shouldUseSessionLocale: false) }}/orders/{{ $order->id }}">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3">
                                    <x-filament::badge color="{{ $order->status->badgeColor() }}">{{ $order->status->label() }}</x-filament::badge>
                                </td>
                                <td class="px-6 py-3 text-end font-semibold text-gray-900 dark:text-white">{{ format_price($order->totalAmount()) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>
