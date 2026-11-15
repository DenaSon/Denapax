<?php

namespace App\Http\Controllers;

use App\Application\Actions\CustomerOtpLogin;
use App\Application\Actions\StoreCart;
use App\Support\IranMobile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAuthController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('cart.index');
        }

        return view('store.login');
    }

    public function requestCode(Request $request, CustomerOtpLogin $login): RedirectResponse
    {
        $request->validate(['mobile' => 'required|string|max:24']);
        $mobile = IranMobile::normalize((string) $request->input('mobile'));

        $login->request($mobile, (string) $request->ip());
        $request->session()->put('customer_otp_mobile', $mobile);

        return back()->with('status', 'کد ورود ارسال شد.');
    }

    public function verify(Request $request, CustomerOtpLogin $login, StoreCart $cart): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        $mobile = $request->session()->get('customer_otp_mobile');

        if (! is_string($mobile)) {
            return back()->withErrors(['code' => 'ابتدا شماره موبایل را وارد کنید.']);
        }

        $customer = $login->verify($mobile, (string) $data['code']);
        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();
        $request->session()->forget('customer_otp_mobile');
        $cart->mergeGuestCart($customer);

        return redirect()->intended(route('cart.index'))->with('status', 'ورود شما با موفقیت انجام شد.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
