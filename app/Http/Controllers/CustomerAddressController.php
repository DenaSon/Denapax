<?php

namespace App\Http\Controllers;

use App\Application\Actions\SaveCustomerAddress;
use App\Models\CustomerAddress;
use App\Support\IranMobile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('store.addresses.index', [
            'addresses' => $request->user('customer')->addresses()
                ->orderByDesc('is_default')->orderByDesc('id')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('store.addresses.form', [
            'address' => new CustomerAddress,
            'returnTo' => $this->returnTo($request),
        ]);
    }

    public function store(Request $request, SaveCustomerAddress $addresses): RedirectResponse
    {
        $addresses->execute($request->user('customer'), $this->attributes($request));

        return redirect()->route($this->returnTo($request) === 'checkout' ? 'checkout.index' : 'addresses.index')
            ->with('status', 'نشانی تحویل ثبت شد.');
    }

    public function edit(Request $request, int $address): View
    {
        return view('store.addresses.form', [
            'address' => $request->user('customer')->addresses()->findOrFail($address),
            'returnTo' => $this->returnTo($request),
        ]);
    }

    public function update(Request $request, int $address, SaveCustomerAddress $addresses): RedirectResponse
    {
        $addresses->execute($request->user('customer'), $this->attributes($request), $address);

        return redirect()->route($this->returnTo($request) === 'checkout' ? 'checkout.index' : 'addresses.index')
            ->with('status', 'نشانی تحویل به‌روزرسانی شد.');
    }

    public function makeDefault(Request $request, int $address, SaveCustomerAddress $addresses): RedirectResponse
    {
        $addresses->setDefault($request->user('customer'), $address);

        return back()->with('status', 'نشانی پیش‌فرض تغییر کرد.');
    }

    public function destroy(Request $request, int $address, SaveCustomerAddress $addresses): RedirectResponse
    {
        $addresses->delete($request->user('customer'), $address);

        return back()->with('status', 'نشانی حذف شد.');
    }

    private function returnTo(Request $request): string
    {
        return $request->input('return') === 'checkout' ? 'checkout' : 'addresses';
    }

    private function attributes(Request $request): array
    {
        $data = $request->validate([
            'label' => 'nullable|string|max:80',
            'recipient_name' => 'required|string|max:220',
            'recipient_mobile' => 'required|string|max:24',
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address_line' => 'required|string|min:8|max:2000',
            'postal_code' => 'required|string|max:16',
        ]);

        $data['recipient_mobile'] = IranMobile::normalize($data['recipient_mobile']);
        $data['postal_code'] = strtr($data['postal_code'], [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return $data;
    }
}
