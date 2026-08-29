<?php

use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\PaymentFormRedirectController;
use App\Http\Controllers\SitemapController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Storefront\Cart;
use App\Livewire\Storefront\Checkout;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\OrderConfirmation;
use App\Livewire\Storefront\OrderHistory;
use App\Livewire\Storefront\ProductCatalog;
use App\Livewire\Storefront\ProductDetail;
use App\Livewire\Storefront\VendorShopPage;
use App\Livewire\Storefront\Wishlist;
use App\Livewire\Vendor\Dashboard as VendorDashboard;
use App\Livewire\Vendor\Earnings as VendorEarnings;
use App\Livewire\Vendor\Onboarding\ApplicationForm as VendorApplicationForm;
use App\Livewire\Vendor\Onboarding\ApplicationStatus as VendorApplicationStatus;
use App\Livewire\Vendor\OrderManager as VendorOrderManager;
use App\Livewire\Vendor\ProductManager\Form as VendorProductForm;
use App\Livewire\Vendor\ProductManager\Index as VendorProductIndex;
use App\Livewire\Vendor\ReviewManager as VendorReviewManager;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/products', ProductCatalog::class)->name('products.index');
Route::get('/products/{slug}', ProductDetail::class)->name('products.show');
Route::get('/store/{slug}', VendorShopPage::class)->name('store.show');

Route::middleware('guest')->group(function () {
    Route::get('/register', Register::class)->name('register');
    Route::get('/login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect()->route('home')->with('status', __('Your email address has been verified.'));
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:1,1'])->name('verification.send');

Route::middleware('auth')->group(function () {
    Route::get('/cart', Cart::class)->name('cart');
    Route::get('/checkout', Checkout::class)->name('checkout');
    Route::get('/orders', OrderHistory::class)->name('orders.index');
    Route::get('/orders/{orderNumber}/confirmation', OrderConfirmation::class)->name('orders.confirmation');
    Route::get('/wishlist', Wishlist::class)->name('wishlist');

    Route::get('/become-a-vendor', VendorApplicationForm::class)->name('vendor.apply');
    Route::get('/vendor/status', VendorApplicationStatus::class)->name('vendor.status');
});

// Vendor dashboard: everything here assumes an Approved VendorProfile — the
// "no profile yet" / "pending" / "rejected" / "suspended" cases are all
// redirected out by EnsureVendorApproved before a route handler ever runs.
Route::prefix('vendor')->name('vendor.')->middleware(['auth', 'vendor.approved'])->group(function () {
    Route::get('/', VendorDashboard::class)->name('dashboard');

    Route::get('/products', VendorProductIndex::class)->name('products.index');
    Route::get('/products/create', VendorProductForm::class)->name('products.create');
    Route::get('/products/{product}/edit', VendorProductForm::class)->name('products.edit');

    Route::get('/orders', VendorOrderManager::class)->name('orders.index');

    Route::get('/reviews', VendorReviewManager::class)->name('reviews.index');
    Route::get('/earnings', VendorEarnings::class)->name('earnings');
});

// Gateway redirect-back callbacks arrive unauthenticated — the customer's
// browser is mid-redirect from the gateway and may not carry an active
// session. Ownership is established from the callback/order data itself,
// not from auth middleware.
Route::match(['get', 'post'], '/payments/{gateway}/callback', PaymentCallbackController::class)->name('payments.callback');
Route::get('/payments/{gateway}/redirect', PaymentFormRedirectController::class)->name('payments.form-redirect');

Route::get('/orders/failed', fn () => view('orders.failed'))->name('orders.failed');
