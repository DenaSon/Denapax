<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود مدیر | دنا</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-base-200 p-4">
    <div class="card w-full max-w-md bg-base-100 p-8 shadow">
        <h1 class="mb-5 text-2xl font-bold">ورود به مدیریت دنا</h1>
        @if ($errors->any())
            <div role="alert" class="alert alert-error mb-4">ایمیل یا رمز عبور نامعتبر است.</div>
        @endif
        <form method="POST" action="{{ url('/login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="label">ایمیل مدیر</label>
                <input class="input w-full" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            </div>
            <div>
                <label for="password" class="label">رمز عبور</label>
                <input class="input w-full" id="password" type="password" name="password" required autocomplete="current-password">
            </div>
            <x-button type="submit" label="ورود" class="btn-primary w-full" />
        </form>
        <p class="mt-4 text-sm opacity-70">ساخت حساب مدیر فقط توسط مسئول فروشگاه انجام می‌شود.</p>
    </div>
</body>
</html>
