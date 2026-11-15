<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\GatewayPaymentAttempt;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        Gate::authorize('orders.view');

        return view('livewire.admin.dashboard', [
            'awaitingPaymentCount' => Order::query()->where('order_status', 'awaiting_payment')->count(),
            'readyToPrepareCount' => Order::query()
                ->where('order_status', 'confirmed')->where('payment_status', 'approved')->count(),
            'preparingCount' => Order::query()->where('order_status', 'preparing')->count(),
            'shippedCount' => Order::query()->where('order_status', 'shipped')->count(),
            'recentOrders' => Order::query()->latest('id')->limit(5)->get(),
            'pendingReceiptCount' => Gate::allows('payments.view')
                ? PaymentReceipt::query()->where('status', 'pending')->count() : null,
            'unresolvedGatewayCount' => Gate::allows('payments.view')
                ? GatewayPaymentAttempt::query()->whereNotIn('status', ['verified', 'cleared_unpaid'])->count() : null,
            'failedNotificationCount' => NotificationLog::query()->where('status', 'failed')->count(),
            'categoryCount' => Gate::allows('catalog.manage') ? Category::count() : null,
            'productCount' => Gate::allows('catalog.manage') ? Product::count() : null,
            'publishedCount' => Gate::allows('catalog.manage') ? Product::published()->count() : null,
        ])->layout('layouts.admin');
    }
}
