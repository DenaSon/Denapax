<?php

namespace App\Livewire\Admin\Orders;

use App\Application\Actions\TransitionOrder;
use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class OrderDetailPage extends Component
{
    public int $orderId;

    public string $carrier = 'پست جمهوری اسلامی ایران';

    public string $trackingCode = '';

    public function mount(Order $order): void
    {
        Gate::authorize('orders.view');
        $this->orderId = $order->id;
        if ($order->shipment) {
            $this->carrier = $order->shipment->carrier;
            $this->trackingCode = $order->shipment->tracking_code ?? '';
        }
    }

    public function change(string $action, TransitionOrder $transition): void
    {
        $this->validate([
            'carrier' => 'required|string|max:80',
            'trackingCode' => $action === 'ship' ? 'required|string|max:120' : 'nullable|string|max:120',
        ]);

        try {
            $transition->execute(auth()->user(), $this->orderId, $action, $this->carrier, $this->trackingCode);
            session()->flash('status', 'وضعیت سفارش به‌روزرسانی شد.');
        } catch (ValidationException $exception) {
            $this->addError('order', collect($exception->errors())->flatten()->first());

            return;
        }
    }

    public function render()
    {
        Gate::authorize('orders.view');

        return view('livewire.admin.orders.order-detail-page', [
            'order' => Order::with(['items', 'shipment', 'receipts.reviewer'])
                ->findOrFail($this->orderId),
        ])->layout('layouts.admin');
    }
}
