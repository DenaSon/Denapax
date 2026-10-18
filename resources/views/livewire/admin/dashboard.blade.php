<div class="space-y-6">
    <h1 class="text-2xl font-bold">داشبورد فروشگاه دنا</h1>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-card title="دسته‌بندی‌ها"><p class="text-3xl font-bold">{{ $categoryCount }}</p></x-card>
        <x-card title="محصولات"><p class="text-3xl font-bold">{{ $productCount }}</p></x-card>
        <x-card title="محصولات منتشرشده"><p class="text-3xl font-bold">{{ $publishedCount }}</p></x-card>
    </div>
    @can('catalog.manage')
        <x-button label="مدیریت محصولات" link="{{ route('admin.products') }}" class="btn-primary" />
    @endcan
</div>
