@component('layouts.store', ['title' => 'پیام‌های من'])
    <div class="mb-8">
        <p class="text-xs font-bold tracking-widest text-[#b18056]">ACCOUNT / INBOX</p>
        <h1 class="mt-2 text-3xl font-extrabold">پیام‌های سفارش</h1>
        <p class="mt-2 text-sm text-[#687969]">وضعیت پرداخت و ارسال سفارش‌ها در این بخش ثبت می‌شود.</p>
    </div>
    <div class="space-y-4">
        @forelse ($notifications as $notification)
            <article class="rounded-2xl border border-[#d8e0d5] {{ $notification->read_at ? 'bg-white/60' : 'bg-white' }} p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-extrabold">{{ $notification->title }}</h2>
                    <span class="text-xs text-[#687969]">{{ $notification->created_at->format('Y/m/d H:i') }}</span>
                </div>
                <p class="mt-3 text-sm leading-8 text-[#4f6c55]">{{ $notification->body }}</p>
                @unless ($notification->read_at)
                    <form class="mt-4" method="POST" action="{{ route('customer.notifications.read', $notification->id) }}">
                        @csrf
                        <button type="submit" class="text-xs font-bold underline underline-offset-4">خوانده شد</button>
                    </form>
                @endunless
            </article>
        @empty
            <div class="rounded-2xl bg-white p-10 text-center text-[#687969]">هنوز پیامی ندارید.</div>
        @endforelse
    </div>
    <div class="mt-8">{{ $notifications->links() }}</div>
@endcomponent
