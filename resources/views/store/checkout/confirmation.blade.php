@component('layouts.store', ['title' => 'جزئیات سفارش'])
    @php
        $paid = $order->payment_status === 'approved';
        $reviewing = $order->payment_status === 'under_review';
        $attempt = $order->gatewayAttempts->sortByDesc('id')->first();
        $receipt = $order->receipts->sortByDesc('id')->first();
        $blockingAttempt = $order->gatewayAttempts->sortBy('id')->first(fn ($entry) => $entry->blocksNewPayment());
        $gatewayBusy = $blockingAttempt !== null;
        $attempt = $blockingAttempt ?: $attempt;
        $canPay = $order->order_status === 'awaiting_payment' && in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true);
        $canUploadReceipt = $canPay && ! $gatewayBusy;
        $card = $order->payment_instructions_snapshot['card_number'] ?? '';
        $cardHolder = $order->payment_instructions_snapshot['card_holder_name'] ?? '';
    @endphp
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-bold tracking-widest text-[#b18056]">MY ORDER / DENAPAX</p>
            <h1 class="mt-2 text-3xl font-extrabold">سفارش {{ $order->public_reference }}</h1>
            <p class="mt-2 text-sm text-[#687969]">ثبت‌شده در {{ $order->created_at->format('Y/m/d H:i') }}</p>
        </div>
        <a href="{{ route('orders.index') }}" class="text-sm font-semibold underline underline-offset-4">همه سفارش‌های من</a>
    </div>

    <section class="rounded-3xl bg-white p-6 shadow-sm md:p-10">
        @if ($paid)
            <div role="status" class="mb-8 rounded-2xl bg-[#e7f1e6] p-5 text-[#245034]">
                <h2 class="text-lg font-extrabold">پرداخت تأیید شد ✓</h2>
                <p class="mt-2 text-sm leading-7">سفارش برای آماده‌سازی و ارسال در اختیار فروشگاه قرار گرفت.</p>
                @if ($attempt?->status === 'verified')
                    <p class="mt-2 text-sm">شماره مرجع زرین‌پال: <span dir="ltr">{{ $attempt->reference_id }}</span></p>
                @endif
            </div>
        @elseif ($reviewing)
            <div role="status" class="mb-8 rounded-2xl bg-[#fff2db] p-5 text-[#85581b]">
                <h2 class="text-lg font-extrabold">رسید دریافت شد؛ در انتظار بررسی</h2>
                <p class="mt-2 text-sm leading-7">نیازی به پرداخت یا آپلود مجدد نیست. نتیجه بررسی همین‌جا نمایش داده می‌شود.</p>
            </div>
        @elseif ($order->order_status === 'canceled')
            <div class="mb-8 rounded-2xl bg-[#f5e5e1] p-5 text-[#7b3e30]">این سفارش لغو شده است.</div>
        @elseif ($canPay)
            <div class="mb-8 rounded-2xl border border-[#d9e1d5] bg-[#f7f8f1] p-5">
                <h2 class="text-xl font-extrabold">تکمیل پرداخت</h2>
                <p class="mt-2 text-sm leading-7 text-[#5e745f]">مبلغ قابل پرداخت: <strong>{{ \App\Support\Money::formatToman($order->total_irr) }} تومان</strong></p>
                @if ($attempt?->status === 'requires_review')
                    <p role="alert" class="mt-4 rounded-xl bg-[#fff2db] p-4 text-sm leading-7">پرداختی ثبت شده اما با وضعیت سفارش هم‌خوانی ندارد. لطفاً دوباره پرداخت نکنید و با پشتیبانی هماهنگ کنید.</p>
                @elseif ($gatewayBusy)
                    <p role="status" class="mt-4 text-sm leading-7 text-[#85581b]">نتیجه پرداخت قبلی هنوز قطعی نیست؛ ممکن است بانک وجه را دریافت کرده باشد. لطفاً تا روشن‌شدن نتیجه، دوباره پرداخت یا کارت‌به‌کارت نکنید.</p>
                    @if ($supportContact)
                        <p class="mt-2 text-sm leading-7">برای پیگیری با پشتیبانی فروشگاه تماس بگیرید: <span dir="ltr" class="inline-block">{{ $supportContact }}</span></p>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-3">
                        @if ($attempt->status === 'redirected' && $gatewayEnabled && $attempt->authority && $attempt->created_at->greaterThan(now()->subMinutes(45)))
                            <a href="{{ app(\App\Integrations\Payments\ZarinpalGateway::class)->paymentUrl($attempt->authority) }}"
                               rel="nofollow" class="rounded-full bg-[#19392f] px-6 py-3 text-sm font-bold text-white">بازگشت به درگاه</a>
                        @endif
                        @if ($attempt->authority)
                            <form action="{{ route('payments.zarinpal.recheck', $order->order_number) }}" method="POST">
                                @csrf
                                <button type="submit" class="rounded-full border border-[#345944] px-6 py-3 text-sm font-bold">بررسی نتیجه پرداخت</button>
                            </form>
                        @endif
                    </div>
                @else
                    @if ($gatewayEnabled)
                        <form action="{{ route('payments.zarinpal.start', $order->order_number) }}" method="POST" class="mt-5">
                            @csrf
                            <button type="submit" class="w-full rounded-full bg-[#19392f] px-7 py-4 text-base font-extrabold text-white shadow-sm hover:bg-[#2d5741] sm:w-auto">
                                پرداخت امن با زرین‌پال
                            </button>
                        </form>
                        <p class="mt-3 text-xs text-[#6c7c6c]">پس از پرداخت به همین فروشگاه برمی‌گردید؛ وضعیت پرداخت به‌صورت خودکار استعلام می‌شود.</p>
                    @else
                        <p class="mt-4 rounded-xl bg-[#fff1df] p-4 text-sm">پرداخت زرین‌پال هنوز برای فروشگاه فعال نشده است.</p>
                    @endif
                    @if ($canUploadReceipt && preg_match('/^[0-9]{16}$/', $card))
                        <details class="mt-6 rounded-2xl border border-[#d5ded2] bg-white p-5" @if (! $gatewayEnabled) open @endif>
                            <summary class="cursor-pointer text-sm font-bold text-[#315740]">روش جایگزین: کارت‌به‌کارت و بارگذاری رسید</summary>
                            <div class="mt-5 space-y-3 text-sm leading-7">
                                <p>شماره کارت:</p>
                                <p class="select-all text-lg font-extrabold tracking-wider" dir="ltr">{{ implode(' ', str_split($card, 4)) }}</p>
                                <p>به نام: <strong>{{ $cardHolder }}</strong></p>
                                <p class="text-xs text-[#687969]">پس از انتقال دقیق مبلغ سفارش، تصویر رسید را ارسال کنید. تأیید نهایی پس از بررسی واریز بانکی انجام می‌شود.</p>
                                <form method="POST" action="{{ route('orders.receipts.store', $order->order_number) }}" enctype="multipart/form-data" class="space-y-4 border-t border-[#e1e8df] pt-5">
                                    @csrf
                                    <label for="receipt-file" class="block font-bold">تصویر رسید (JPG، PNG یا WebP؛ حداکثر ۸ مگابایت)</label>
                                    <input id="receipt-file" name="receipt" type="file" accept="image/jpeg,image/png,image/webp" required
                                           class="block w-full rounded-xl border border-[#ccd9cb] p-3">
                                    <button type="submit" class="rounded-full bg-[#19392f] px-6 py-3 font-bold text-white">ارسال رسید برای بررسی</button>
                                </form>
                            </div>
                        </details>
                    @endif
                @endif
                @if ($order->payment_status === 'rejected' && $receipt)
                    <p class="mt-4 rounded-xl bg-[#fce9e6] p-4 text-sm text-red-800">رسید قبلی تأیید نشد: {{ $receipt->rejection_reason }}. می‌توانید پرداخت آنلاین انجام دهید یا رسید صحیح بفرستید.</p>
                @endif
            </div>
        @endif

        <h2 class="text-lg font-extrabold">اقلام سفارش</h2>
        <div class="mt-4 divide-y divide-[#e0e7dc]">
            @foreach ($order->items as $item)
                <div class="flex items-start justify-between gap-4 py-4 text-sm">
                    <div>
                        <p class="font-bold">{{ $item->product_title_snapshot }} / {{ $item->variant_title_snapshot }}</p>
                        <p class="mt-1 text-[#687969]">تعداد: {{ $item->quantity }}</p>
                    </div>
                    <span class="shrink-0 font-semibold">{{ \App\Support\Money::formatToman((int) $item->line_total_irr) }} تومان</span>
                </div>
            @endforeach
        </div>
        <dl class="mt-5 space-y-3 border-t border-[#d6ded2] pt-5 text-sm">
            <div class="flex justify-between"><dt>کالاها</dt><dd>{{ \App\Support\Money::formatToman($order->goods_subtotal_irr) }} تومان</dd></div>
            <div class="flex justify-between"><dt>ارسال</dt><dd>{{ \App\Support\Money::formatToman($order->shipping_fee_irr) }} تومان</dd></div>
            <div class="flex justify-between border-t border-[#d6ded2] pt-4 text-lg font-extrabold"><dt>جمع نهایی</dt><dd>{{ \App\Support\Money::formatToman($order->total_irr) }} تومان</dd></div>
        </dl>
        <section class="mt-8 rounded-xl bg-[#f4f1e6] p-5 text-sm leading-8">
            <h2 class="font-bold">گیرنده و نشانی تحویل</h2>
            <p>{{ $order->recipient_name }} · {{ $order->recipient_mobile }}</p>
            <p>{{ $order->province }}، {{ $order->city }}، {{ $order->address_line }}</p>
            <p>کد پستی: {{ $order->postal_code }}</p>
        </section>

        @if ($receipt)
            <section class="mt-6 rounded-xl border border-[#d4ded3] p-5 text-sm leading-7">
                <h2 class="font-bold">آخرین رسید ارسالی</h2>
                <p class="mt-2">وضعیت:
                    {{ match ($receipt->status) { 'pending' => 'در انتظار بررسی', 'approved' => 'تأیید شده', 'rejected' => 'رد شده', default => $receipt->status } }}
                </p>
                <a class="mt-2 inline-block font-bold underline underline-offset-4" href="{{ route('orders.receipts.image', ['orderNumber' => $order->order_number, 'receipt' => $receipt->id]) }}" target="_blank" rel="noopener">مشاهده تصویر رسید خودم</a>
            </section>
        @endif

        @if ($order->shipment)
            <section class="mt-6 rounded-xl bg-[#e8f1e7] p-5 text-sm leading-7">
                <h2 class="font-bold">وضعیت ارسال</h2>
                <p>شرکت حمل: {{ $order->shipment->carrier }}</p>
                @if ($order->shipment->tracking_code)
                    <p>کد رهگیری: <span dir="ltr">{{ $order->shipment->tracking_code }}</span></p>
                @endif
                <p>{{ $order->shipment->delivered_at ? 'تحویل‌شده' : 'در مسیر ارسال' }}</p>
            </section>
        @elseif ($paid)
            <p class="mt-6 text-sm text-[#5f745f]">وضعیت بسته‌بندی و کد رهگیری پس از ارسال، در همین صفحه نمایش داده می‌شود.</p>
        @endif
    </section>
@endcomponent
