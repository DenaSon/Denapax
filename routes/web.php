<?php

use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerNotificationController;
use App\Http\Controllers\CustomerOrdersController;
use App\Http\Controllers\CustomerReceiptController;
use App\Http\Controllers\GatewayPaymentController;
use App\Http\Controllers\PrivatePaymentReceiptController;
use App\Http\Controllers\PublicLandingController;
use App\Http\Controllers\PublicProductController;
use App\Http\Controllers\PublicSitemapController;
use App\Http\Controllers\StoreCartController;
use App\Http\Controllers\WebCheckoutController;
use App\Http\Middleware\EnsureActiveStaff;
use App\Livewire\Admin\Catalog\CategoriesPage;
use App\Livewire\Admin\Catalog\ProductEditor;
use App\Livewire\Admin\Catalog\ProductsPage;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\NotificationLogPage;
use App\Livewire\Admin\Orders\OrderDetailPage;
use App\Livewire\Admin\Orders\OrdersPage;
use App\Livewire\Admin\PaymentReviewPage;
use App\Livewire\Admin\SettingsPage;
use App\Livewire\Admin\ShippingSettingsPage;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicLandingController::class)->name('home');
Route::get('/products/{slug}', PublicProductController::class)->name('products.show');
Route::get('/sitemap.xml', PublicSitemapController::class)->name('sitemap');

// Public payment gateway return; NEVER regard callback parameters as proof of payment.
Route::get('/payments/zarinpal/callback', [GatewayPaymentController::class, 'callback'])
    ->middleware('throttle:60,1')->name('payments.zarinpal.callback');

Route::get('/cart', [StoreCartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [StoreCartController::class, 'add'])->middleware('throttle:60,1')->name('cart.add');
Route::patch('/cart/items/{variantId}', [StoreCartController::class, 'update'])->middleware('throttle:60,1')->whereNumber('variantId')->name('cart.update');
Route::delete('/cart/items/{variantId}', [StoreCartController::class, 'remove'])->middleware('throttle:60,1')->whereNumber('variantId')->name('cart.remove');

Route::get('/customer/login', [CustomerAuthController::class, 'show'])->name('customer.login');
Route::post('/customer/otp/request', [CustomerAuthController::class, 'requestCode'])->middleware('throttle:10,1')->name('customer.otp.request');
Route::post('/customer/otp/verify', [CustomerAuthController::class, 'verify'])->middleware('throttle:15,1')->name('customer.otp.verify');
Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

Route::middleware('auth:customer')->group(function (): void {
    Route::get('/account/addresses', [CustomerAddressController::class, 'index'])->name('addresses.index');
    Route::get('/account/addresses/new', [CustomerAddressController::class, 'create'])->name('addresses.create');
    Route::post('/account/addresses', [CustomerAddressController::class, 'store'])->name('addresses.store');
    Route::get('/account/addresses/{address}/edit', [CustomerAddressController::class, 'edit'])->whereNumber('address')->name('addresses.edit');
    Route::put('/account/addresses/{address}', [CustomerAddressController::class, 'update'])->whereNumber('address')->name('addresses.update');
    Route::post('/account/addresses/{address}/default', [CustomerAddressController::class, 'makeDefault'])->whereNumber('address')->name('addresses.default');
    Route::delete('/account/addresses/{address}', [CustomerAddressController::class, 'destroy'])->whereNumber('address')->name('addresses.destroy');

    Route::get('/checkout', [WebCheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/preview', [WebCheckoutController::class, 'preview'])->middleware('throttle:30,1')->name('checkout.preview');
    Route::get('/checkout/review', [WebCheckoutController::class, 'review'])->name('checkout.review');
    Route::post('/checkout/confirm', [WebCheckoutController::class, 'confirm'])->middleware('throttle:15,1')->name('checkout.confirm');
    Route::get('/orders', [CustomerOrdersController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderNumber}', [WebCheckoutController::class, 'confirmation'])->name('orders.confirmation');
    Route::post('/orders/{orderNumber}/payment/zarinpal', [GatewayPaymentController::class, 'start'])
        ->middleware('throttle:10,1')->name('payments.zarinpal.start');
    Route::post('/orders/{orderNumber}/payment/recheck', [GatewayPaymentController::class, 'recheck'])
        ->middleware('throttle:10,1')->name('payments.zarinpal.recheck');
    Route::post('/orders/{orderNumber}/receipts', [CustomerReceiptController::class, 'store'])
        ->middleware('throttle:5,1')->name('orders.receipts.store');
    Route::get('/orders/{orderNumber}/receipts/{receipt}/image', [CustomerReceiptController::class, 'image'])
        ->whereNumber('receipt')->name('orders.receipts.image');

    Route::get('/account/notifications', [CustomerNotificationController::class, 'index'])->name('customer.notifications.index');
    Route::post('/account/notifications/{notification}/read', [CustomerNotificationController::class, 'markRead'])
        ->whereNumber('notification')->name('customer.notifications.read');
});

Route::middleware(['auth:web', EnsureActiveStaff::class])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/categories', CategoriesPage::class)->middleware('can:catalog.manage')->name('categories');
    Route::get('/products', ProductsPage::class)->middleware('can:catalog.manage')->name('products');
    Route::get('/products/new', ProductEditor::class)->middleware('can:catalog.manage')->name('products.create');
    Route::get('/products/{product}', ProductEditor::class)->middleware('can:catalog.manage')->name('products.edit');
    Route::get('/notifications', NotificationLogPage::class)->middleware('can:orders.view')->name('notifications');
    Route::get('/orders', OrdersPage::class)->middleware('can:orders.view')->name('orders');
    Route::get('/orders/{order}', OrderDetailPage::class)->middleware('can:orders.view')->name('orders.show');
    Route::get('/payments', PaymentReviewPage::class)->middleware('can:payments.view')->name('payments');
    Route::get('/payments/receipts/{receipt}/image', PrivatePaymentReceiptController::class)
        ->middleware('can:payments.view')->name('payments.receipt.image');
    Route::get('/shipping', ShippingSettingsPage::class)->middleware('can:shipping.manage')->name('shipping');
    Route::get('/settings', SettingsPage::class)->middleware('can:settings.manage')->name('settings');
});
