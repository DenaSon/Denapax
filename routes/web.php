<?php

use App\Http\Controllers\PrivatePaymentReceiptController;
use App\Http\Controllers\PublicLandingController;
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

Route::middleware(['auth', EnsureActiveStaff::class])->prefix('admin')->name('admin.')->group(function (): void {
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
