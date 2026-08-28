<?php

use App\Enums\Locale;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(app()->getLocale());
});

Route::get('/language/{locale}', [LanguageController::class, 'switch'])
    ->where('locale', implode('|', Locale::values()))
    ->name('locale.switch');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::group([
    'prefix' => '{locale}',
    'where' => ['locale' => implode('|', Locale::values())],
], function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
});
