<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\ProductCatalog;
use App\Livewire\Storefront\ProductDetail;
use App\Livewire\Storefront\VendorShopPage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');

Route::get('/products', ProductCatalog::class)->name('products.index');
Route::get('/products/{slug}', ProductDetail::class)->name('products.show');
Route::get('/store/{slug}', VendorShopPage::class)->name('store.show');

Route::middleware('guest')->group(function () {
    Route::get('/register', Register::class)->name('register');
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');
