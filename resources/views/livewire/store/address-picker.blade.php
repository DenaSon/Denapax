<div class="mt-4 space-y-3">
    <div wire:loading.delay.short wire:target="choose" role="status" class="rounded-xl bg-[#edf4eb] p-3 text-sm text-[#315743]">در حال محاسبه هزینه ارسال…</div>
    @foreach ($addresses as $address)
        <article wire:key="checkout-address-{{ $address->id }}" class="rounded-2xl border p-4 {{ $selectedAddressId === $address->id ? 'border-[#346349] bg-[#edf4eb]' : 'border-[#d8ddd3] bg-white' }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-bold">{{ $address->label ?: $address->city }}</p>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold">
                        @if ($address->is_default)
                            <span class="rounded-full bg-[#edf2e8] px-2.5 py-1 text-[#426449]">نشانی پیش‌فرض</span>
                        @endif
                        @if ($selectedAddressId === $address->id)
                            <span class="rounded-full bg-[#dcebdd] px-2.5 py-1 text-[#315c45]" aria-current="true">✓ انتخاب‌شده</span>
                        @endif
                    </div>
                </div>
                <div class="flex flex-none items-center gap-3">
                    <a wire:navigate href="{{ route('addresses.edit', ['address' => $address->id, 'return' => 'checkout']) }}" class="store-inline-link">ویرایش</a>
                    @if ($selectedAddressId !== $address->id)
                        <form method="GET" action="{{ route('checkout.index') }}">
                            <button type="submit" name="address_id" value="{{ $address->id }}"
                                    wire:click.prevent="choose({{ $address->id }})" wire:loading.attr="disabled" wire:target="choose"
                                    class="store-button store-button-outline !min-h-11 !px-4 !py-2 text-sm">
                                انتخاب این نشانی
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <p class="mt-3 text-sm font-semibold text-[#405b46]">{{ $address->recipient_name }}</p>
            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-[#657967]">
                <span>موبایل: <bdi dir="ltr" class="font-semibold tabular-nums text-[#365540]">{{ $address->recipient_mobile }}</bdi></span>
                <span>کد پستی: <bdi dir="ltr" class="font-semibold tabular-nums text-[#365540]">{{ $address->postal_code }}</bdi></span>
            </div>
            <p class="mt-2 text-sm leading-7 text-[#657967]">{{ $address->province }}، {{ $address->city }}، {{ $address->address_line }}</p>
        </article>
    @endforeach
</div>
