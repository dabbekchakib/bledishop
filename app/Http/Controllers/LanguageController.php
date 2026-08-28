<?php

namespace App\Http\Controllers;

use App\Services\LocalizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function switch(Request $request, string $locale, LocalizationService $localization): RedirectResponse
    {
        abort_unless($localization->isAvailable($locale), 404);

        $localization->setActive($locale);

        if ($request->user()) {
            $request->user()->forceFill(['locale' => $locale])->save();
        }

        return redirect($localization->switchTarget($locale, $request));
    }
}
