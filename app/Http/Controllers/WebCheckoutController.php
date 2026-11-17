<?php

namespace App\Http\Controllers;

use App\Application\Actions\CreateOrder;
use App\Application\Actions\PrepareCheckout;
use App\Application\Actions\StoreCart;
use App\Integrations\Payments\ZarinpalGateway;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WebCheckoutController extends Controller
{
    private const SESSION_KEY = 'web_checkout_preview';

    public function index(Request $request, StoreCart $cart, PrepareCheckout $checkout): View|RedirectResponse
    {
        $customer = $request->user('customer');
        $lines = $cart->lines($customer);

        if ($lines->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'ابتدا محصولی به سبد خرید اضافه کنید.']);
        }

        $addresses = $customer->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();
        $requestedId = $request->query('address_id');

        if ($requestedId !== null && (! is_string($requestedId) || ! ctype_digit($requestedId))) {
            abort(404);
        }

        $selectedAddress = $requestedId === null
            ? $addresses->first()
            : $addresses->firstWhere('id', (int) $requestedId);

        if ($requestedId !== null && $selectedAddress === null) {
            abort(404);
        }

        $quote = null;
        $checkoutKey = null;
        $quoteError = null;

        if ($lines->contains(fn (array $line): bool => ! $line['available'])) {
            $quoteError = 'یکی از اقلام سبد دیگر قابل خرید نیست. ابتدا سبد را اصلاح کنید.';
            $request->session()->forget(self::SESSION_KEY);
        } elseif ($selectedAddress !== null) {
            try {
                $quote = $checkout->execute($customer, $selectedAddress->id);
                $fingerprint = $checkout->fingerprint($quote);
                $context = $this->validContext($request);

                $checkoutKey = $context !== null
                    && (int) $context['address_id'] === $selectedAddress->id
                    && hash_equals($context['fingerprint'], $fingerprint)
                    ? $context['checkout_key']
                    : (string) Str::uuid();

                $request->session()->put(self::SESSION_KEY, [
                    'customer_id' => $customer->id,
                    'address_id' => $selectedAddress->id,
                    'checkout_key' => $checkoutKey,
                    'fingerprint' => $fingerprint,
                    'expires_at' => now()->addMinutes(30)->timestamp,
                ]);
            } catch (ValidationException $exception) {
                $quoteError = collect($exception->errors())->flatten()->first()
                    ?: 'امکان محاسبه مبلغ سفارش وجود ندارد. سبد را بازبینی کنید.';
                $request->session()->forget(self::SESSION_KEY);
            }
        } else {
            $request->session()->forget(self::SESSION_KEY);
        }

        return view('store.checkout.index', [
            'lines' => $lines,
            'subtotal' => $lines->sum('total_irr'),
            'addresses' => $addresses,
            'selectedAddress' => $selectedAddress,
            'quote' => $quote,
            'checkoutKey' => $checkoutKey,
            'quoteError' => $quoteError,
        ]);
    }

    public function preview(Request $request, PrepareCheckout $checkout): RedirectResponse
    {
        $data = $request->validate(['address_id' => 'required|integer|min:1']);
        $customer = $request->user('customer');
        $quote = $checkout->execute($customer, (int) $data['address_id']);

        $request->session()->put(self::SESSION_KEY, [
            'customer_id' => $customer->id,
            'address_id' => (int) $data['address_id'],
            'checkout_key' => (string) Str::uuid(),
            'fingerprint' => $checkout->fingerprint($quote),
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        return redirect()->route('checkout.review');
    }

    public function review(Request $request, PrepareCheckout $checkout, ZarinpalGateway $gateway): View|RedirectResponse
    {
        $context = $this->validContext($request);

        if ($context === null) {
            return redirect()->route('checkout.index')->withErrors(['checkout' => 'ابتدا آدرس تحویل را انتخاب کنید.']);
        }

        try {
            $quote = $checkout->execute($request->user('customer'), (int) $context['address_id']);
        } catch (ValidationException $exception) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('checkout.index')->withErrors($exception->errors());
        }

        if (hash_equals($context['fingerprint'], $checkout->fingerprint($quote)) === false) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('checkout.index')->withErrors([
                'checkout' => 'مبلغ، آدرس یا مشخصات سبد تغییر کرده است. پیش‌نمایش جدید بگیرید.',
            ]);
        }

        return view('store.checkout.review', [
            'quote' => $quote,
            'checkoutKey' => $context['checkout_key'],
            'gatewayEnabled' => $gateway->enabled(),
        ]);
    }

    public function confirm(Request $request, CreateOrder $create): RedirectResponse
    {
        $data = $request->validate(['checkout_key' => 'required|uuid']);
        $context = $this->validContext($request);

        if ($context === null || hash_equals($context['checkout_key'], $data['checkout_key']) === false) {
            return redirect()->route('checkout.index')->withErrors([
                'checkout' => 'مهلت تأیید سفارش پایان یافته است. پیش‌نمایش جدید بگیرید.',
            ]);
        }

        try {
            $order = $create->execute(
                $request->user('customer'),
                (int) $context['address_id'],
                $context['checkout_key'],
                $context['fingerprint'],
            );
        } catch (ValidationException $exception) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('checkout.index')->withErrors($exception->errors());
        }

        return redirect()->route('orders.confirmation', ['orderNumber' => $order->order_number]);
    }

    public function confirmation(Request $request, string $orderNumber): View
    {
        $order = $request->user('customer')->orders()
            ->where('order_number', $orderNumber)
            ->with(['items', 'receipts', 'shipment', 'gatewayAttempts'])
            ->firstOrFail();

        return view('store.checkout.confirmation', [
            'order' => $order,
            'gatewayEnabled' => app(ZarinpalGateway::class)->enabled(),
            'supportContact' => StoreSetting::query()->find(1)?->support_contact,
        ]);
    }

    private function validContext(Request $request): ?array
    {
        $context = $request->session()->get(self::SESSION_KEY);

        if (is_array($context) === false
            || (int) ($context['customer_id'] ?? 0) !== (int) $request->user('customer')->id
            || is_numeric($context['address_id'] ?? null) === false
            || Str::isUuid((string) ($context['checkout_key'] ?? '')) === false
            || preg_match('/^[a-f0-9]{64}$/D', (string) ($context['fingerprint'] ?? '')) !== 1
            || (int) ($context['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return $context;
    }
}
