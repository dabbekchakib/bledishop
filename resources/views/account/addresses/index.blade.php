<x-account.layout active="addresses" :title="__('account.addresses_title')">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold text-heading">{{ __('account.addresses_title') }}</h1>
        <a href="{{ localized_route('account.addresses.create') }}" class="btn-primary">{{ __('account.add_address') }}</a>
    </div>
    <p class="mt-1 text-sm text-text-muted">{{ __('account.addresses_intro') }}</p>

    @if (session('status') === 'address-created')
        <p class="mt-4 rounded-xl border border-success/40 bg-surface px-4 py-3 text-sm text-success">{{ __('account.address_created') }}</p>
    @elseif (session('status') === 'address-updated')
        <p class="mt-4 rounded-xl border border-success/40 bg-surface px-4 py-3 text-sm text-success">{{ __('account.address_updated') }}</p>
    @elseif (session('status') === 'address-deleted')
        <p class="mt-4 rounded-xl border border-border bg-surface px-4 py-3 text-sm text-text">{{ __('account.address_deleted') }}</p>
    @endif

    @if ($addresses->isEmpty())
        <div class="mt-6 rounded-2xl border border-dashed border-border bg-surface/50 px-6 py-12 text-center">
            <p class="text-sm text-text-muted">{{ __('account.no_addresses') }}</p>
            <a href="{{ localized_route('account.addresses.create') }}" class="btn-primary mt-5 inline-flex justify-center !px-6">
                {{ __('account.add_address') }}
            </a>
        </div>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ($addresses as $address)
                <div class="relative rounded-2xl border border-border bg-surface p-5 {{ $address->is_default ? 'border-primary' : '' }}">
                    @if ($address->is_default)
                        <span class="mb-2 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold text-primary" style="background-color: color-mix(in srgb, var(--color-primary, #2563EB) 12%, transparent);">
                            {{ __('account.default_address') }}
                        </span>
                    @endif
                    @if ($address->label)
                        <h2 class="text-sm font-bold uppercase tracking-wide text-text-muted">{{ $address->label }}</h2>
                    @endif
                    <address class="mt-2 text-sm not-italic leading-relaxed text-text">
                        {{ $address->fullName() }}<br>
                        {{ $address->phone }}<br>
                        {{ $address->address }}<br>
                        {{ $address->city }}{{ $address->postal_code ? ' '.$address->postal_code : '' }}<br>
                        {{ $address->country }}
                    </address>
                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ localized_route('account.addresses.edit', ['address' => $address]) }}" class="text-sm font-medium text-primary hover:underline">
                            {{ __('account.edit') }}
                        </a>
                        <form method="POST" action="{{ localized_route('account.addresses.destroy', ['address' => $address]) }}" onsubmit="return confirm('{{ __('account.confirm_delete_address') }}')">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-sm font-medium text-danger hover:underline">{{ __('account.delete') }}</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</x-account.layout>
