<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $orderStatus = '';

    public string $paymentStatus = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedOrderStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('orders.view');

        $filters = $this->validate([
            'search' => 'string|max:80',
            'orderStatus' => 'nullable|in:awaiting_payment,confirmed,preparing,shipped,delivered,canceled',
            'paymentStatus' => 'nullable|in:awaiting_receipt,under_review,rejected,approved',
            'dateFrom' => 'nullable|date_format:Y-m-d',
            'dateTo' => 'nullable|date_format:Y-m-d',
        ]);

        return view('livewire.admin.orders.orders-page', [
            'orders' => Order::query()
                ->when($filters['search'] !== '', fn ($q) => $q->where('order_number', 'like', '%'.$filters['search'].'%'))
                ->when($filters['orderStatus'] !== '', fn ($q) => $q->where('order_status', $filters['orderStatus']))
                ->when($filters['paymentStatus'] !== '', fn ($q) => $q->where('payment_status', $filters['paymentStatus']))
                ->when($filters['dateFrom'] !== '', fn ($q) => $q->whereDate('created_at', '>=', $filters['dateFrom']))
                ->when($filters['dateTo'] !== '', fn ($q) => $q->whereDate('created_at', '<=', $filters['dateTo']))
                ->latest()->paginate(15),
        ])->layout('layouts.admin');
    }
}
