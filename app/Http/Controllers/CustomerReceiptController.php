<?php

namespace App\Http\Controllers;

use App\Application\Actions\SubmitPaymentReceipt;
use App\Models\PaymentReceipt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CustomerReceiptController extends Controller
{
    public function store(Request $request, string $orderNumber, SubmitPaymentReceipt $submit): RedirectResponse
    {
        $order = $request->user('customer')->orders()->where('order_number', $orderNumber)->firstOrFail();

        $request->validate([
            'receipt' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $file = $request->file('receipt');
        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };

        if ($extension === null || ! $file->isValid()) {
            throw ValidationException::withMessages(['receipt' => 'فرمت تصویر رسید قابل قبول نیست.']);
        }

        $path = $file->storeAs('payment-receipts', Str::uuid().'.'.$extension, 'receipts');

        if (! is_string($path)) {
            throw ValidationException::withMessages(['receipt' => 'بارگذاری رسید انجام نشد.']);
        }

        try {
            $submit->execute($request->user('customer'), $order->id, $path);
        } catch (Throwable $exception) {
            Storage::disk('receipts')->delete($path);

            throw $exception;
        }

        return redirect()->route('orders.confirmation', ['orderNumber' => $orderNumber])
            ->with('status', 'رسید دریافت شد؛ نتیجه پس از بررسی واریز بانکی در همین صفحه نمایش داده می‌شود.');
    }

    public function image(Request $request, string $orderNumber, int $receipt): StreamedResponse
    {
        $order = $request->user('customer')->orders()->where('order_number', $orderNumber)->firstOrFail();
        $uploaded = $order->receipts()->findOrFail($receipt);

        return Storage::disk('receipts')->response($uploaded->storage_path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
