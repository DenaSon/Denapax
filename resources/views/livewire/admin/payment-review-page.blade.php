<div class="space-y-5">
    <h1 class="text-2xl font-bold">بررسی دستی رسیدهای کارت‌به‌کارت</h1>
    <p class="text-sm opacity-70">دریافت تصویر به‌معنای پرداخت قطعی نیست. مبلغ و شماره سفارش را با تراکنش واقعی بانک مطابقت دهید و سپس تأیید کنید.</p>

    @if($receiptId !== null)
        <p><a class="link" href="{{ route('admin.payments') }}">← مشاهده همه رسیدها</a></p>
    @endif

    <x-card>
        <label class="form-control">
            <span class="label">وضعیت رسید</span>
            <select wire:model.live="filter" class="select max-w-xs">
                <option value="pending">در انتظار بررسی</option>
                <option value="approved">تأییدشده</option>
                <option value="rejected">ردشده</option>
                <option value="all">همه رسیدها</option>
            </select>
        </label>
    </x-card>

    @forelse($receipts as $receipt)
        <x-card wire:key="receipt-{{ $receipt->id }}">
            <div class="space-y-3">
                <div class="flex flex-wrap justify-between gap-2">
                    <div>
                        <p class="font-bold">سفارش {{ $receipt->order->public_reference }} · تلاش {{ $receipt->attempt_number }}</p>
                        <p class="text-sm">مبلغ سفارش: {{ \App\Support\Money::formatToman($receipt->order->total_irr) }} تومان</p>
                        <p class="text-sm">وضعیت: {{ $receipt->status }} · {{ $receipt->submitted_at?->format('Y-m-d H:i') }}</p>
                    </div>
                    <a href="{{ route('admin.payments.receipt.image', $receipt) }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline">مشاهده تصویر خصوصی رسید</a>
                </div>

                @if($receipt->status === 'rejected')
                    <p class="text-error">دلیل رد: {{ $receipt->rejection_reason }}</p>
                @endif
                @if($receipt->reviewer)
                    <p class="text-xs opacity-70">بررسی توسط: {{ $receipt->reviewer->name }}</p>
                @endif

                @can('payments.review')
                    @if($receipt->status === 'pending' && $receipt->order->payment_status === 'under_review')
                        <x-button label="تأیید پس از بررسی بانک" class="btn-success" wire:confirm="آیا واریز بانکی را مستقلاً بررسی کرده‌اید؟" wire:click="approve({{ $receipt->id }})" />
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="min-w-[240px] flex-1">
                                <x-textarea label="دلیل رد (الزامی)" wire:model="rejectionReason" />
                            </div>
                            <x-button label="رد رسید" class="btn-error btn-outline" wire:confirm="آیا از رد این رسید مطمئن هستید؟" wire:click="reject({{ $receipt->id }})" />
                        </div>
                    @endif
                @endcan
            </div>
        </x-card>
    @empty
        <x-card>رسیدی برای نمایش وجود ندارد.</x-card>
    @endforelse

    <div>{{ $receipts->links() }}</div>
</div>
