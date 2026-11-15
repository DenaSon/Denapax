<?php

namespace App\Http\Controllers;

use App\Application\Actions\StartGatewayPayment;
use App\Application\Actions\VerifyGatewayPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GatewayPaymentController extends Controller
{
    public function start(Request $request, string $orderNumber, StartGatewayPayment $start): RedirectResponse
    {
        $order = $request->user('customer')->orders()->where('order_number', $orderNumber)->firstOrFail();

        return redirect()->away($start->execute($request->user('customer'), $order->id));
    }

    public function callback(Request $request, VerifyGatewayPayment $verify): RedirectResponse
    {
        // All browser callback values are untrusted. Confirmation is exclusively
        // based on a server-to-server verification of stored amount and authority.
        $authority = $request->query('Authority');
        $attempt = is_string($authority) ? $verify->execute($authority) : null;

        if (! $attempt) {
            return redirect()->route('orders.index')->withErrors(['payment' => 'تراکنش موردنظر پیدا نشد.']);
        }

        $target = route('orders.confirmation', ['orderNumber' => $attempt->order->order_number]);

        return match ($attempt->status) {
            'verified' => redirect($target)->with('status', 'پرداخت با موفقیت تأیید شد.'),
            'requires_review' => redirect($target)->withErrors([
                'payment' => 'وضعیت واریز با سفارش هم‌خوانی ندارد و نیازمند بررسی پشتیبانی است. مجدداً پرداخت نکنید.',
            ]),
            'verification_pending', 'verifying' => redirect($target)->withErrors([
                'payment' => 'نتیجه پرداخت هنوز قطعی نیست. لطفاً در همین صفحه دوباره وضعیت پرداخت را بررسی کنید.',
            ]),
            default => redirect($target)->withErrors(['payment' => 'پرداخت تأیید نشد. می‌توانید دوباره تلاش کنید.']),
        };
    }

    public function recheck(Request $request, string $orderNumber, VerifyGatewayPayment $verify): RedirectResponse
    {
        $order = $request->user('customer')->orders()->where('order_number', $orderNumber)->firstOrFail();
        $attempt = $order->gatewayAttempts()->latest('id')->whereNotNull('authority')->first();

        if (! $attempt || $attempt->status === 'verified') {
            return redirect()->route('orders.confirmation', ['orderNumber' => $orderNumber]);
        }

        $verified = $verify->execute($attempt->authority);

        return redirect()->route('orders.confirmation', ['orderNumber' => $orderNumber])
            ->with($verified?->status === 'verified' ? 'status' : 'notice',
                $verified?->status === 'verified'
                    ? 'پرداخت تأیید شد.'
                    : 'هنوز تأیید قطعی پرداخت دریافت نشده است.');
    }
}
