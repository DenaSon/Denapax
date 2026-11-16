<div class="space-y-5">
    <div>
        <p class="admin-eyebrow">بررسی پرداخت‌های دستی</p>
        <h1 class="admin-page-title">رسیدهای کارت‌به‌کارت</h1>
        <p class="admin-description">تصویر رسید تأیید پرداخت نیست. ابتدا مبلغ و شماره سفارش را با واریز قطعی بانک تطبیق دهید؛ سپس تصمیم بگیرید.</p>
    </div>

    @if($receiptId !== null)
        <a wire:navigate class="admin-inline-link" href="{{ route('admin.payments') }}">بازگشت به همه رسیدها</a>
    @endif

    <section class="admin-surface space-y-3" aria-label="فیلتر وضعیت رسید">
        <label class="form-control max-w-xs">
            <span class="label font-bold">نمایش رسیدها</span>
            <select wire:model.live="filter" class="select select-bordered w-full">
                <option value="pending">در انتظار بررسی</option>
                <option value="approved">تأییدشده</option>
                <option value="rejected">ردشده</option>
                <option value="all">همه رسیدها</option>
            </select>
        </label>
    </section>

    @forelse($receipts as $receipt)
        <article class="admin-surface space-y-4" wire:key="receipt-{{ $receipt->id }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-2">
                    <a wire:navigate class="admin-inline-link text-base" href="{{ route('admin.orders.show', $receipt->order) }}">سفارش {{ $receipt->order->public_reference }}</a>
                    <p class="text-sm">ارسال رسید شماره {{ $receipt->attempt_number }} · {{ $receipt->submitted_at?->format('Y/m/d H:i') }}</p>
                    <p class="admin-money text-lg font-extrabold">{{ \App\Support\Money::formatToman($receipt->order->total_irr) }} تومان</p>
                    <span class="admin-state admin-state-{{ \App\Support\AdminLabels::tone($receipt->status) }}">{{ \App\Support\AdminLabels::receipt($receipt->status) }}</span>
                </div>
                <a href="{{ route('admin.payments.receipt.image', $receipt) }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline w-full sm:w-auto">مشاهده تصویر خصوصی رسید</a>
            </div>

            @if($receipt->status === 'rejected')
                <div class="admin-alert admin-alert-danger">دلیل رد: {{ $receipt->rejection_reason }}</div>
            @endif
            @if($receipt->reviewer)
                <p class="text-xs text-[#627463]">بررسی‌شده توسط {{ $receipt->reviewer->name }}</p>
            @endif

            @can('payments.review')
                @if($receipt->status === 'pending' && $receipt->order->payment_status === 'under_review')
                    <div class="space-y-3 border-t border-[#e1e8de] pt-4">
                        <div class="admin-alert admin-alert-warning">پیش از تأیید، واریز واقعی را مستقل از تصویر رسید در بانک بررسی کنید.</div>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="btn btn-success w-full sm:w-auto"
                                    wire:confirm="آیا واریز بانکی را مستقلاً بررسی کرده‌اید و مبلغ با سفارش تطبیق دارد؟"
                                    wire:click="approve({{ $receipt->id }})" wire:loading.attr="disabled"
                                    wire:target="approve({{ $receipt->id }})">تأیید پس از تطبیق بانک</button>
                            @if($rejectingReceiptId !== $receipt->id)
                                <button type="button" class="btn btn-error btn-outline w-full sm:w-auto"
                                        wire:click="startRejection({{ $receipt->id }})">رد رسید و ثبت دلیل</button>
                            @endif
                        </div>
                        @if($rejectingReceiptId === $receipt->id)
                            <div class="space-y-3 rounded-xl border border-[#e8d1cc] bg-[#fff7f5] p-4">
                                <label for="receipt-rejection-reason" class="block text-sm font-bold">دلیل رد این رسید (الزامی)</label>
                                <textarea id="receipt-rejection-reason" rows="3" wire:model="rejectionReason"
                                          class="textarea textarea-bordered w-full"
                                          placeholder="دلیل روشن برای مشتری بنویسید؛ اطلاعات محرمانه بانکی را وارد نکنید"></textarea>
                                @error('rejectionReason')<p role="alert" class="text-sm text-error">{{ $message }}</p>@enderror
                                <div class="flex flex-wrap gap-3">
                                    <button type="button" class="btn btn-error w-full sm:w-auto"
                                            wire:confirm="آیا از رد این رسید و ارسال دلیل به مشتری اطمینان دارید؟"
                                            wire:click="reject({{ $receipt->id }})" wire:loading.attr="disabled"
                                            wire:target="reject({{ $receipt->id }})">ثبت رد رسید</button>
                                    <button type="button" class="btn btn-ghost" wire:click="cancelRejection">انصراف</button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            @endcan
        </article>
    @empty
        <div class="admin-surface py-10 text-center text-sm text-[#627463]">رسیدی در این وضعیت وجود ندارد.</div>
    @endforelse

    {{ $receipts->links() }}
</div>
