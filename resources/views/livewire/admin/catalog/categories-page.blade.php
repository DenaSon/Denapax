<div class="space-y-5">
    <h1 class="text-2xl font-bold">مدیریت دسته‌بندی‌ها</h1>
    <x-card title="{{ $editing ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید' }}">
        <form wire:submit="save" class="space-y-4">
            <x-input label="نام" wire:model="name" />
            <x-input label="شناسه انگلیسی (slug)" wire:model="slug" />
            <x-input label="ترتیب نمایش" type="number" min="0" wire:model="sortOrder" />
            <label class="label cursor-pointer justify-start gap-3">
                <input type="checkbox" class="checkbox" wire:model="isActive">
                فعال باشد
            </label>
            <div class="flex gap-2">
                <x-button type="submit" label="ذخیره" class="btn-primary" />
                <x-button type="button" label="جدید" wire:click="resetForm" />
            </div>
        </form>
    </x-card>
    <x-card title="فهرست دسته‌بندی‌ها">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>نام</th><th>شناسه</th><th>وضعیت</th><th>اقدام</th></tr></thead>
                <tbody>
                @forelse($categories as $category)
                    <tr wire:key="category-{{ $category->id }}">
                        <td>{{ $category->name }}</td>
                        <td dir="ltr">{{ $category->slug }}</td>
                        <td>{{ $category->is_active ? 'فعال' : 'غیرفعال' }}</td>
                        <td><x-button label="ویرایش" class="btn-sm" wire:click="edit({{ $category->id }})" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4">هنوز دسته‌بندی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
