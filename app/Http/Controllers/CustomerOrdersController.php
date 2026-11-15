<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CustomerOrdersController extends Controller
{
    public function index(Request $request): View
    {
        return view('store.orders.index', [
            'orders' => $request->user('customer')->orders()
                ->with(['shipment', 'gatewayAttempts'])
                ->latest('id')
                ->paginate(12),
        ]);
    }
}
