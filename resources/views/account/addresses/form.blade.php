<x-account.layout
    active="addresses"
    :title="$address ? __('account.edit_address') : __('account.add_address')"
>

    <h1 class="text-2xl font-extrabold text-heading">{{ $address ? __('account.edit_address') : __('account.add_address') }}</h1>

    <form method="POST"
          action="{{ $address ? localized_route('account.addresses.update', ['address' => $address]) : localized_route('account.addresses.store') }}"
          class="mt-6 max-w-2xl rounded-2xl border border-border bg-surface p-6">
        @csrf
        @if ($address)
            @method('put')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="label" class="block text-sm font-medium text-heading">{{ __('account.address_label') }}</label>
                <input id="label" name="label" type="text" value="{{ old('label', $address?->label) }}"
                       class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary">
                @error('label')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-heading">{{ __('account.first_name') }}</label>
                        <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $address?->first_name) }}"
                               class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" required>
                        @error('first_name')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-heading">{{ __('account.last_name') }}</label>
                        <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $address?->last_name) }}"
                               class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" required>
                        @error('last_name')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-heading">{{ __('account.phone') }}</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $address?->phone) }}"
                       class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" required>
                @error('phone')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-heading">{{ __('account.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email', $address?->email) }}"
                       class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" autocomplete="off">
                @error('email')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="address" class="block text-sm font-medium text-heading">{{ __('account.address_line') }}</label>
                <input id="address" name="address" type="text" value="{{ old('address', $address?->address) }}"
                       class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" required>
                @error('address')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="city" class="block text-sm font-medium text-heading">{{ __('account.city') }}</label>
                <input id="city" name="city" type="text" value="{{ old('city', $address?->city) }}"
                       class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" required>
                @error('city')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="postal_code" class="block text-sm font-medium text-heading">{{ __('account.postal_code') }}</label>
                <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $address?->postal_code) }}"
                       class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary">
                @error('postal_code')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="country" class="block text-sm font-medium text-heading">{{ __('account.country') }}</label>
                <input id="country" name="country" type="text" value="{{ old('country', $address?->country) }}"
                       class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary" required>
                @error('country')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="notes" class="block text-sm font-medium text-heading">{{ __('account.address_notes') }}</label>
                <textarea id="notes" name="notes" rows="2"
                          class="mt-1 w-full rounded-lg border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary">{{ old('notes', $address?->notes) }}</textarea>
                @error('notes')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-text">
                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address?->is_default ?? false)) class="rounded border-border text-primary focus:ring-primary">
                    {{ __('account.make_default') }}
                </label>
            </div>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="submit" class="btn-primary">{{ __('account.save') }}</button>
            <a href="{{ localized_route('account.addresses.index') }}" class="text-sm font-medium text-text-muted hover:text-primary">{{ __('account.cancel') }}</a>
        </div>
    </form>

</x-account.layout>
