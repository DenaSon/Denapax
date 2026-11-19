<?php

namespace App\Http\Controllers;

use App\Application\Actions\SaveCustomerAddress;
use App\Models\CustomerAddress;
use App\Support\CustomerAddressInput;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            'customerMobile' => $request->user('customer')->mobile,
        ]);
    }

    public function store(Request $request, SaveCustomerAddress $addresses): RedirectResponse
    {
        $saved = $addresses->execute($request->user('customer'), $this->attributes($request));

        return $this->returnTo($request) === 'checkout'
            ? redirect()->route('checkout.index', ['address_id' => $saved->id])->with('status', 'نشانی تحویل ثبت شد.')
            : redirect()->route('addresses.index')->with('status', 'نشانی تحویل ثبت شد.');
    }

    public function edit(Request $request, int $address): View
    {
        return view('store.addresses.form', [
            'address' => $request->user('customer')->addresses()->findOrFail($address),
            'returnTo' => $this->returnTo($request),
            'customerMobile' => $request->user('customer')->mobile,
        ]);
    }

    public function update(Request $request, int $address, SaveCustomerAddress $addresses): RedirectResponse
    {
        $saved = $addresses->execute($request->user('customer'), $this->attributes($request), $address);

        return $this->returnTo($request) === 'checkout'
            ? redirect()->route('checkout.index', ['address_id' => $saved->id])->with('status', 'نشانی تحویل به‌روزرسانی شد.')
            : redirect()->route('addresses.index')->with('status', 'نشانی تحویل به‌روزرسانی شد.');
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
        return Validator::make(
            CustomerAddressInput::normalize($request->all()),
            CustomerAddressInput::rules(),
            CustomerAddressInput::messages(),
        )->validate();
    }
}
