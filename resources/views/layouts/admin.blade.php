<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17382c">
    <title>مدیریت دناپکس | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="admin-shell min-h-screen text-base-content">
    <a href="#admin-main" class="admin-skip-link">رفتن به محتوای اصلی</a>
    <header class="admin-header">
        <div class="mx-auto flex w-full max-w-[1500px] flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-7">
            <a href="{{ route('admin.dashboard') }}" class="admin-brand" aria-label="دناپکس، داشبورد مدیریت">
                <span class="admin-brand-mark" aria-hidden="true">⌃</span>
                <span class="flex flex-col"><span class="text-lg font-extrabold leading-tight">دناپکس</span><span class="text-xs font-medium text-[#c8d9cc]">مدیریت فروشگاه</span></span>
            </a>
            <div class="flex items-center gap-2 sm:gap-4">
                <span class="hidden text-sm text-[#d7e5d8] sm:inline">{{ auth('web')->user()->name }}</span>
                <a class="admin-header-link" href="{{ route('home') }}" target="_blank" rel="noopener noreferrer">مشاهده فروشگاه</a>
                <form action="{{ url('/logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="admin-header-link admin-header-exit">خروج</button>
                </form>
            </div>
        </div>
    </header>
    <div class="mx-auto max-w-[1500px] px-4 py-5 sm:px-7 lg:py-8">
        <details class="admin-mobile-nav mb-5 lg:hidden">
            <summary class="admin-mobile-nav-toggle">منوی مدیریت <span aria-hidden="true">☰</span></summary>
            <div class="px-3 pb-3">@include('admin.partials.navigation')</div>
        </details>
        <div class="flex items-start gap-6">
            <aside class="admin-sidebar sticky top-6 hidden w-64 shrink-0 p-4 lg:block">
                @include('admin.partials.navigation')
            </aside>
            <main id="admin-main" class="admin-main min-w-0 flex-1 space-y-6">
                @if (session('status'))
                    <div role="status" class="admin-alert admin-alert-success">{{ session('status') }}</div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
