<div class="space-y-5">
    <div>
        <p class="admin-eyebrow">تطبیق مالی</p>
        <h1 class="admin-page-title">تراکنش‌های زرین‌پال</h1>
        <p class="admin-description">وضعیت تراکنش‌ها را بررسی کنید؛ نتیجه نامشخص یا گذشت زمان هرگز به معنای عدم واریز نیست.</p>
    </div>

    <div class="admin-alert admin-alert-warning" role="note">
        <strong>قانون ایمنی پرداخت:</strong> تا وقتی بانک و پنل پذیرنده عدم واریز را تأیید نکرده‌اند،
        اجازه پرداخت مجدد ندهید. برای تراکنش قطعی یا متعارض، پرونده را برای بررسی مالی باز نگه دارید.
    </div>

    @if ($errors->any())
        <div role="alert" class="admin-alert admin-alert-danger">
            <ul class="list-disc pr-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="admin-surface" aria-label="فیلتر تراکنش‌های درگاه">
        <label class="form-control max-w-sm">
            <span class="label font-bold">وضعیت نمایش</span>
            <select wire:model.live="filter" class="select select-bordered w-full">
                <option value="unresolved">نیازمند بررسی / نتیجه نامشخص</option>
                <option value="verified">تأییدشده</option>
                <option value="cleared">واریزنشده پس از تطبیق</option>
                <option value="all">همه تراکنش‌ها</option>
            </select>
        </label>
    </section>

    @forelse ($attempts as $attempt)
        <article class="admin-surface space-y-4" wire:key="gateway-attempt-{{ $attempt->id }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 space-y-2">
                    <a class="admin-inline-link text-base" href="{{ route('admin.orders.show', $attempt->order->id) }}">
                        سفارش {{ $attempt->order->public_reference }}
                    </a>
                    <p class="text-xs text-[#687b69]">تلاش شماره {{ $attempt->id }} · {{ $attempt->created_at->format('Y/m/d H:i') }}</p>
                    <p class="admin-money text-lg font-extrabold">{{ \App\Support\Money::formatToman($attempt->amount_irr) }} تومان</p>
                </div>
                <span class="admin-state admin-state-{{ \App\Support\AdminLabels::tone($attempt->status) }}">
                    {{ \App\Support\AdminLabels::gateway($attempt->status) }}
                </span>
            </div>
            <dl class="grid gap-3 rounded-xl bg-[#f3f6f0] p-4 text-sm sm:grid-cols-2">
                <div class="min-w-0">
                    <dt class="mb-1 text-xs font-bold text-[#607464]">Authority</dt>
                    <dd dir="ltr" class="admin-copy text-left font-semibold">{{ $attempt->authority ?: 'ثبت نشده' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="mb-1 text-xs font-bold text-[#607464]">شماره مرجع</dt>
                    <dd dir="ltr" class="admin-copy text-left font-semibold">{{ $attempt->reference_id ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="mb-1 text-xs font-bold text-[#607464]">آخرین استعلام درگاه</dt>
                    <dd>{{ $attempt->inquiry_status ? \App\Support\AdminLabels::inquiry($attempt->inquiry_status) : 'هنوز انجام نشده' }}</dd>
                    <dd class="mt-1 text-xs">{{ $attempt->inquiry_checked_at?->format('Y/m/d H:i') ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="mb-1 text-xs font-bold text-[#607464]">وضعیت فنی</dt>
                    <dd class="admin-copy font-mono text-xs">{{ $attempt->status }}</dd>
                    @if ($attempt->last_error)
                        <dd class="admin-copy mt-1 text-xs text-[#98513e]">{{ $attempt->last_error }}</dd>
                    @endif
                </div>
            </dl>

            @if ($attempt->status === 'requires_review')
                <div role="alert" class="admin-alert admin-alert-danger">
                    احتمال دریافت قطعی وجه همراه با تعارض سفارش وجود دارد. این تراکنش را نمی‌توان «واریزنشده» ثبت کرد.
                    ابتدا با پنل پذیرنده و مسئول مالی تطبیق دهید.
                </div>
            @endif

            @if ($attempt->resolved_at)
                <div class="rounded-xl bg-[#e8f0e4] p-4 text-sm leading-7">
                    <p><strong>ثبت تصمیم:</strong> {{ $attempt->resolver?->name ?: 'کاربر حذف‌شده' }} · {{ $attempt->resolved_at->format('Y/m/d H:i') }}</p>
                    <p class="mt-2 whitespace-pre-line">{{ $attempt->resolution_note }}</p>
                </div>
            @endif

            @can('payments.review')
                @if ($attempt->status !== 'verified' && $attempt->status !== 'cleared_unpaid')
                    <div class="space-y-3 border-t border-[#e1e8de] pt-4">
                        @if ($attempt->authority)
                            <button type="button" wire:click="inquire({{ $attempt->id }})"
                                    wire:loading.attr="disabled" wire:target="inquire({{ $attempt->id }})"
                                    class="btn btn-primary w-full sm:w-auto">استعلام زنده از زرین‌پال</button>
                            <p class="admin-description">برای آزادسازی سفارش، استعلام تازه FAILED یا REVERSED و تأیید مستقل پنل پذیرنده لازم است.</p>
                        @else
                            <p class="admin-description">Authority دریافت نشده است. حداقل یک ساعت پس از درخواست، گزارش پنل پذیرنده را بررسی و نتیجه را مستند کنید.</p>
                        @endif

                        @if ($attempt->status !== 'requires_review' && $attempt->status !== 'verifying')
                            <div class="rounded-xl border border-[#ecdac1] bg-[#fff9f0] p-4">
                                <label for="gateway-note-{{ $attempt->id }}" class="mb-2 block text-sm font-bold">توضیح مستند عدم واریز (الزامی)</label>
                                <textarea id="gateway-note-{{ $attempt->id }}"
                                          wire:model="resolutionNotes.{{ $attempt->id }}"
                                          rows="2" minlength="20" maxlength="1000"
                                          placeholder="شماره پیگیری تطبیق در پنل پذیرنده؛ بدون اطلاعات محرمانه"
                                          class="textarea textarea-bordered w-full"></textarea>
                                <button type="button" wire:click="clearUnpaid({{ $attempt->id }})"
                                        wire:confirm="آیا در پنل پذیرنده و گزارش بانکی، عدم دریافت یا برگشت قطعی وجه را بررسی کرده‌اید؟"
                                        wire:loading.attr="disabled" wire:target="clearUnpaid({{ $attempt->id }})"
                                        class="btn btn-warning mt-3 w-full sm:w-auto">ثبت «واریزنشده» و آزادسازی سفارش</button>
                            </div>
                        @endif
                    </div>
                @endif
            @endcan
        </article>
    @empty
        <div class="admin-surface py-10 text-center text-sm text-[#627463]">تراکنشی در این وضعیت وجود ندارد.</div>
    @endforelse

    {{ $attempts->links() }}
</div>
