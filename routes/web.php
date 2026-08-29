<?php

use App\Enums\Locale;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Shop\BrandController;
use App\Http\Controllers\Shop\CategoryController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\ShopController;
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

    Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
    Route::get('/recherche', [ShopController::class, 'search'])->name('shop.search');
    Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('shop.category.show');
    Route::get('/brand/{slug}', [BrandController::class, 'show'])->name('shop.brand.show');
    Route::get('/product/{slug}', [ProductController::class, 'show'])->name('shop.product.show');

    Route::get('/cart', [CartController::class, 'show'])->name('shop.cart.show');
    Route::get('/cart/drawer', [CartController::class, 'drawer'])->name('shop.cart.drawer');
    Route::get('/cart/fragments', [CartController::class, 'fragments'])->name('shop.cart.fragments');
    Route::post('/cart/add', [CartController::class, 'add'])->name('shop.cart.add');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('shop.cart.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'remove'])->name('shop.cart.remove');
    Route::delete('/cart', [CartController::class, 'clear'])->name('shop.cart.clear');
    Route::get('/cart/checkout', [CartController::class, 'checkoutPlaceholder'])->name('shop.checkout');
});
