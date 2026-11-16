<div class="mt-4 space-y-3">
    <div wire:loading.delay.short wire:target="choose" role="status" class="rounded-xl bg-[#edf4eb] p-3 text-sm text-[#315743]">در حال محاسبه هزینه ارسال…</div>
    @foreach ($addresses as $address)
        <div wire:key="checkout-address-{{ $address->id }}" class="rounded-2xl border p-4 {{ $selectedAddressId === $address->id ? 'border-[#346349] bg-[#edf4eb]' : 'border-[#d8ddd3] bg-white' }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="font-bold">
                    {{ $address->label ?: $address->city }}
                    @if ($selectedAddressId === $address->id)
                        <span class="mr-2 text-xs font-semibold text-[#38684b]">✓ انتخاب‌شده</span>
                    @endif
                </p>
                <div class="flex items-center gap-3">
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
            <p class="mt-2 text-sm leading-7 text-[#657967]">{{ $address->recipient_name }} · {{ $address->province }}، {{ $address->city }}، {{ $address->address_line }}</p>
        </div>
    @endforeach
</div>
