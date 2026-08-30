<?php

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Services\LocalizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminLocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (! Locale::has($locale) || ! app(LocalizationService::class)->isAvailable($locale)) {
            abort(404);
        }

        $request->session()->put('admin_locale', $locale);

        if (($user = $request->user()) !== null) {
            $user->update(['locale' => $locale]);
        }

        app()->setLocale($locale);

        return redirect()->back();
    }
}
