<div class="space-y-5">
    <h1 class="text-2xl font-bold">تنظیمات فروشگاه</h1>
    <x-card title="اطلاعات نمایش و کارت‌به‌کارت">
        <p class="mb-4 text-sm opacity-70">تغییر اطلاعات کارت فقط روی سفارش‌های جدید اثر خواهد داشت. در زمان ثبت سفارش اطلاعات پرداخت ذخیره می‌شود.</p>
        <form wire:submit="save" class="space-y-4">
            <x-input label="نام فروشگاه" wire:model="storeName" />
            <x-input label="ارتباط با پشتیبانی" wire:model="supportContact" />
            <p class="text-sm opacity-70">برای دکمه گفت‌وگوی مستقیم تلگرام، آیدی مانند @DenaSupport یا لینک https://t.me/DenaSupport وارد کنید. شماره تلفن و متن معمولی فقط به‌صورت اطلاعات تماس نمایش داده می‌شوند.</p>
            <x-input label="شماره کارت (۱۶ رقم)" wire:model="cardNumber" />
            <x-input label="نام دارنده کارت" wire:model="cardHolderName" />
            <x-button type="submit" label="ذخیره تنظیمات" class="btn-primary" />
        </form>
    </x-card>
</div>
