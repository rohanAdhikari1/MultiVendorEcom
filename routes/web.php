<?php

use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\PaymentFormRedirectController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Storefront\Cart;
use App\Livewire\Storefront\Checkout;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\OrderConfirmation;
use App\Livewire\Storefront\OrderHistory;
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

Route::middleware('auth')->group(function () {
    Route::get('/cart', Cart::class)->name('cart');
    Route::get('/checkout', Checkout::class)->name('checkout');
    Route::get('/orders', OrderHistory::class)->name('orders.index');
    Route::get('/orders/{orderNumber}/confirmation', OrderConfirmation::class)->name('orders.confirmation');
});

// Gateway redirect-back callbacks arrive unauthenticated — the customer's
// browser is mid-redirect from the gateway and may not carry an active
// session. Ownership is established from the callback/order data itself,
// not from auth middleware.
Route::match(['get', 'post'], '/payments/{gateway}/callback', PaymentCallbackController::class)->name('payments.callback');
Route::get('/payments/{gateway}/redirect', PaymentFormRedirectController::class)->name('payments.form-redirect');

Route::get('/orders/failed', fn () => view('orders.failed'))->name('orders.failed');
