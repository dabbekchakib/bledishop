<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class AddressController extends Controller
{
    /**
     * List the authenticated user's saved addresses.
     */
    public function index(Request $request): View
    {
        $addresses = $request->user()->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        return view('account.addresses.index', [
            'addresses' => $addresses,
            'user' => $request->user(),
        ]);
    }

    public function create(): View
    {
        return view('account.addresses.form', [
            'address' => null,
        ]);
    }

    public function store(AddressRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $makeDefault = (bool) ($data['is_default'] ?? false);

        if ($makeDefault || $request->user()->addresses()->count() === 0) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $request->user()->addresses()->create([
            ...$data,
            'is_default' => $makeDefault || $request->user()->addresses()->count() === 0,
        ]);

        return Redirect::route('account.addresses.index', ['locale' => $request->route('locale')])
            ->with('status', 'address-created');
    }

    public function edit(Request $request, string $locale, CustomerAddress $address): View|RedirectResponse
    {
        if ($address->user_id !== $request->user()->getKey()) {
            abort(403);
        }

        return view('account.addresses.form', [
            'address' => $address,
        ]);
    }

    public function update(AddressRequest $request, string $locale, CustomerAddress $address): RedirectResponse
    {
        if ($address->user_id !== $request->user()->getKey()) {
            abort(403);
        }

        $data = $request->validated();
        $makeDefault = (bool) ($data['is_default'] ?? false);

        if ($makeDefault) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->forceFill([...$data, 'is_default' => $makeDefault])->save();

        return Redirect::route('account.addresses.index', ['locale' => $request->route('locale')])
            ->with('status', 'address-updated');
    }

    public function destroy(Request $request, string $locale, CustomerAddress $address): RedirectResponse
    {
        if ($address->user_id !== $request->user()->getKey()) {
            abort(403);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $request->user()->addresses()->first()?->update(['is_default' => true]);
        }

        return Redirect::route('account.addresses.index', ['locale' => $request->route('locale')])
            ->with('status', 'address-deleted');
    }
}
