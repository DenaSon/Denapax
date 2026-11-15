<?php

namespace App\Http\Controllers;

use App\Application\Actions\StoreCart;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreCartController extends Controller
{
    public function index(Request $request, StoreCart $cart): View
    {
        $lines = $cart->lines($request->user('customer'));

        return view('store.cart', [
            'lines' => $lines,
            'subtotal' => $lines->sum('total_irr'),
        ]);
    }

    public function add(Request $request, StoreCart $cart): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1|max:1000',
        ]);

        $cart->add($request->user('customer'), (int) $data['variant_id'], (int) $data['quantity']);

        return redirect()->route('cart.index')->with('status', 'محصول به سبد خرید اضافه شد.');
    }

    public function update(Request $request, StoreCart $cart, int $variantId): RedirectResponse
    {
        $data = $request->validate(['quantity' => 'required|integer|min:0|max:1000']);
        $cart->setQuantity($request->user('customer'), $variantId, (int) $data['quantity']);

        return redirect()->route('cart.index');
    }

    public function remove(Request $request, StoreCart $cart, int $variantId): RedirectResponse
    {
        $cart->setQuantity($request->user('customer'), $variantId, 0);

        return redirect()->route('cart.index');
    }
}
