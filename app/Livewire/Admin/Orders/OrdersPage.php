<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Support\OrderReference;
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
                ->when($filters['search'] !== '', function ($query) use ($filters): void {
                    $search = trim($filters['search']);
                    $id = OrderReference::toId($search);
                    $query->where(function ($q) use ($search, $id): void {
                        $q->where('order_number', 'like', '%'.$search.'%');
                        if ($id !== null) {
                            $q->orWhereKey($id);
                        }
                    });
                })
                ->when($filters['orderStatus'] !== '', fn ($q) => $q->where('order_status', $filters['orderStatus']))
                ->when($filters['paymentStatus'] !== '', fn ($q) => $q->where('payment_status', $filters['paymentStatus']))
                ->when($filters['dateFrom'] !== '', fn ($q) => $q->whereDate('created_at', '>=', $filters['dateFrom']))
                ->when($filters['dateTo'] !== '', fn ($q) => $q->whereDate('created_at', '<=', $filters['dateTo']))
                ->latest()->paginate(15),
        ])->layout('layouts.admin');
    }
}
