<?php

use App\Enums\Locale;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
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
    Route::get('/cart/checkout', [CartController::class, 'checkoutPlaceholder'])->name('shop.checkout.legacy');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('shop.checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('shop.checkout.store');
    Route::get('/commande/{order}/confirmation', [CheckoutController::class, 'confirmation'])
        ->name('shop.order.confirmation');

    require __DIR__.'/auth.php';

    Route::middleware('auth')->group(function () {
        Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');

        Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders.index');
        Route::get('/account/orders/{orderNumber}', [AccountController::class, 'order'])->name('account.orders.show');

        Route::get('/account/profile', [ProfileController::class, 'edit'])->name('account.profile.edit');
        Route::patch('/account/profile', [ProfileController::class, 'update'])->name('account.profile.update');
        Route::delete('/account/profile', [ProfileController::class, 'destroy'])->name('account.profile.destroy');

        Route::get('/account/addresses', [AddressController::class, 'index'])->name('account.addresses.index');
        Route::get('/account/addresses/create', [AddressController::class, 'create'])->name('account.addresses.create');
        Route::post('/account/addresses', [AddressController::class, 'store'])->name('account.addresses.store');
        Route::get('/account/addresses/{address}/edit', [AddressController::class, 'edit'])->name('account.addresses.edit');
        Route::put('/account/addresses/{address}', [AddressController::class, 'update'])->name('account.addresses.update');
        Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');
    });
});
