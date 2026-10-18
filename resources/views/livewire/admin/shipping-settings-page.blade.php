<div class="space-y-5">
    <h1 class="text-2xl font-bold">تنظیمات ارسال سراسری</h1>
    <x-card title="یک مرسوله برای هر سفارش">
        <p class="mb-5 text-sm opacity-70">وزن کالاها به گرم محاسبه می‌شود؛ هزینه‌ها به ریال هستند. تغییر تعرفه روی سفارش‌های ثبت‌شده اثری ندارد.</p>
        <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
            <x-input label="سقف وزن پایه (گرم)" type="number" min="1" wire:model="baseWeightGrams" />
            <x-input label="هزینه پایه (ریال)" type="number" min="0" wire:model="basePriceIrr" />
            <x-input label="گام وزن اضافه (گرم)" type="number" min="1" wire:model="extraStepGrams" />
            <x-input label="هزینه هر گام اضافه (ریال)" type="number" min="0" wire:model="extraStepPriceIrr" />
            <x-input label="وزن بسته‌بندی (گرم)" type="number" min="0" wire:model="packagingWeightGrams" />
            <div class="md:col-span-2">
                <x-button label="ذخیره تعرفه" type="submit" class="btn-primary" />
            </div>
        </form>
    </x-card>
</div>
