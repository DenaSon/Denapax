<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-extrabold">تطبیق تراکنش‌های زرین‌پال</h1>
        <p class="mt-3 text-sm leading-7 opacity-75">
            گذشت زمان یا خطای شبکه به معنای عدم واریز نیست. پیش از مجاز کردن پرداخت مجدد،
            تراکنش را با استعلام زرین‌پال و پنل پذیرنده مطابقت دهید.
            وضعیت PAID یا VERIFIED فقط با تأیید سمت سرور به سفارش اعمال می‌شود.
        </p>
    </div>

    @if ($errors->any())
        <div role="alert" class="alert alert-error">
            <ul class="list-disc pr-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-card>
        <label class="form-control">
            <span class="label">نمایش تلاش‌ها</span>
            <select wire:model.live="filter" class="select max-w-sm">
                <option value="unresolved">نیازمند بررسی / نتیجه نامشخص</option>
                <option value="verified">تأییدشده</option>
                <option value="cleared">تطبیق‌شده / واریز نشده</option>
                <option value="all">همه تلاش‌ها</option>
            </select>
        </label>
    </x-card>

    @forelse ($attempts as $attempt)
        <x-card wire:key="gateway-attempt-{{ $attempt->id }}">
            <div class="space-y-4">
                <div class="flex flex-wrap justify-between gap-3">
                    <div class="space-y-1">
                        <p class="font-bold">
                            <a class="link" href="{{ route('admin.orders.show', $attempt->order->id) }}">
                                سفارش {{ $attempt->order->public_reference }}
                            </a>
                            · تلاش #{{ $attempt->id }}
                        </p>
                        <p class="text-sm">مبلغ: {{ \App\Support\Money::formatToman($attempt->amount_irr) }} تومان</p>
                        <p class="text-sm">وضعیت ثبت‌شده: <strong>{{ $attempt->status }}</strong></p>
                        <p class="text-xs opacity-70">زمان ایجاد: {{ $attempt->created_at->format('Y/m/d H:i') }}</p>
                    </div>
                    <div class="max-w-full space-y-1 text-xs">
                        <p class="break-all">Authority: <span dir="ltr">{{ $attempt->authority ?: 'ثبت نشده' }}</span></p>
                        <p class="break-all">Ref ID: <span dir="ltr">{{ $attempt->reference_id ?: '—' }}</span></p>
                        <p>آخرین استعلام: {{ $attempt->inquiry_status ?: 'انجام نشده' }}
                            · {{ $attempt->inquiry_checked_at?->format('Y/m/d H:i') ?: '—' }}</p>
                        @if ($attempt->last_error)
                            <p class="text-warning">خطای آخر: {{ $attempt->last_error }}</p>
                        @endif
                    </div>
                </div>

                @if ($attempt->status === 'requires_review')
                    <div role="alert" class="rounded-lg border border-error p-3 text-sm">
                        احتمال واریز قطعی همراه با تعارض سفارش وجود دارد. این مورد را با بانک تطبیق دهید؛
                        از این صفحه امکان «واریز نشده» اعلام کردن آن وجود ندارد.
                    </div>
                @endif

                @if ($attempt->resolved_at)
                    <div class="rounded-lg bg-base-200 p-3 text-sm">
                        تطبیق توسط {{ $attempt->resolver?->name ?: 'کاربر حذف‌شده' }}
                        در {{ $attempt->resolved_at->format('Y/m/d H:i') }}
                        <p class="mt-2 whitespace-pre-line">{{ $attempt->resolution_note }}</p>
                    </div>
                @endif

                @can('payments.review')
                    @if ($attempt->status !== 'verified' && $attempt->status !== 'cleared_unpaid')
                        <div class="space-y-3 border-t border-base-300 pt-4">
                            @if ($attempt->authority)
                                <button type="button" wire:click="inquire({{ $attempt->id }})"
                                        wire:loading.attr="disabled" wire:target="inquire({{ $attempt->id }})"
                                        class="btn btn-outline btn-sm">استعلام وضعیت از زرین‌پال</button>
                                <p class="text-xs opacity-70">اجازه ثبت «واریز نشده» فقط با استعلام تازه FAILED یا REVERSED و تطبیق پنل پذیرنده داده می‌شود.</p>
                            @else
                                <p class="text-xs opacity-70">Authority دریافت نشده است. پس از حداقل یک ساعت، پنل پذیرنده را بررسی و نتیجه تطبیق را مستند کنید.</p>
                            @endif
                            @if ($attempt->status !== 'requires_review' && $attempt->status !== 'verifying')
                                <label for="gateway-note-{{ $attempt->id }}" class="block text-sm font-bold">مستند و دلیل اعلام عدم واریز (الزامی)</label>
                                <textarea id="gateway-note-{{ $attempt->id }}"
                                          wire:model="resolutionNotes.{{ $attempt->id }}"
                                          rows="2" minlength="20" maxlength="1000"
                                          placeholder="شماره پیگیری تطبیق در پنل پذیرنده و نتیجه بررسی؛ بدون اطلاعات محرمانه"
                                          class="textarea textarea-bordered w-full"></textarea>
                                <button type="button" wire:click="clearUnpaid({{ $attempt->id }})"
                                        wire:confirm="آیا پنل پذیرنده و وضعیت تراکنش را مستقلاً بررسی کرده‌اید و مطمئن هستید وجهی دریافت نشده یا برگشت خورده است؟"
                                        wire:loading.attr="disabled" wire:target="clearUnpaid({{ $attempt->id }})"
                                        class="btn btn-warning btn-sm">ثبت نتیجه «واریز نشده» و آزادسازی سفارش</button>
                            @endif
                        </div>
                    @endif
                @endcan
            </div>
        </x-card>
    @empty
        <x-card>تراکنشی در این وضعیت وجود ندارد.</x-card>
    @endforelse

    <div>{{ $attempts->links() }}</div>
</div>
