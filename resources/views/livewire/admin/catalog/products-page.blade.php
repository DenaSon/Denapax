<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">مدیریت محصولات</h1>
        <x-button label="محصول جدید" link="{{ route('admin.products.create') }}" class="btn-primary" />
    </div>
    @error('publish') <div role="alert" class="alert alert-error">{{ $message }}</div> @enderror
    <x-card>
        <x-input label="جستجو در نام محصولات" wire:model.live.debounce.300ms="search" />
    </x-card>
    <x-card>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>نام</th><th>دسته</th><th>تنوع‌ها</th><th>وضعیت</th><th>اقدام</th></tr></thead>
                <tbody>
                @forelse($products as $product)
                    <tr wire:key="product-{{ $product->id }}">
                        <td>{{ $product->title }}</td>
                        <td>{{ $product->category->name }}</td>
                        <td>{{ $product->variants->count() }}</td>
                        <td>{{ $product->is_active ? 'منتشرشده' : 'پیش‌نویس' }}</td>
                        <td class="flex gap-2">
                            <x-button label="ویرایش" class="btn-sm" link="{{ route('admin.products.edit', $product) }}" />
                            <x-button label="{{ $product->is_active ? 'غیرفعال' : 'فعال' }}" class="btn-sm" wire:click="toggle({{ $product->id }})" />
                            <x-button label="بایگانی" class="btn-sm btn-error btn-outline" wire:confirm="از بایگانی محصول مطمئن هستید؟" wire:click="archive({{ $product->id }})" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">محصولی یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    </x-card>
</div>
