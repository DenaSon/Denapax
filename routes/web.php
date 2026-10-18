<?php

use App\Http\Middleware\EnsureActiveStaff;
use App\Livewire\Admin\Catalog\CategoriesPage;
use App\Livewire\Admin\Catalog\ProductEditor;
use App\Livewire\Admin\Catalog\ProductsPage;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\SettingsPage;
use App\Livewire\Admin\ShippingSettingsPage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware(['auth', EnsureActiveStaff::class])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/categories', CategoriesPage::class)->middleware('can:catalog.manage')->name('categories');
    Route::get('/products', ProductsPage::class)->middleware('can:catalog.manage')->name('products');
    Route::get('/products/new', ProductEditor::class)->middleware('can:catalog.manage')->name('products.create');
    Route::get('/products/{product}', ProductEditor::class)->middleware('can:catalog.manage')->name('products.edit');
    Route::get('/shipping', ShippingSettingsPage::class)->middleware('can:shipping.manage')->name('shipping');
    Route::get('/settings', SettingsPage::class)->middleware('can:settings.manage')->name('settings');
});
