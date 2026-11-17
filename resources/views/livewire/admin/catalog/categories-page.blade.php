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
            <div class="flex flex-wrap gap-2">
                <x-button type="submit" label="ذخیره" class="btn-primary" />
                <x-button type="button" label="جدید" wire:click="resetForm" />
            </div>
        </form>
    </x-card>
    @error('category') <div role="alert" class="alert alert-error">{{ $message }}</div> @enderror

    <div class="grid gap-3 md:hidden">
        @forelse($categories as $category)
            <article wire:key="mobile-category-{{ $category->id }}" class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="min-w-0 break-words font-bold">{{ $category->name }}</h2>
                    <span class="badge {{ $category->is_active ? 'badge-success' : 'badge-ghost' }} flex-none">
                        {{ $category->is_active ? 'فعال' : 'غیرفعال' }}
                    </span>
                </div>
                <p class="mt-2 break-all text-xs opacity-70" dir="ltr">{{ $category->slug }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-button label="ویرایش" class="btn-sm" wire:click="edit({{ $category->id }})" />
                    @if(! $category->products()->withTrashed()->exists())
                        <x-button label="حذف" class="btn-sm btn-error btn-outline"
                                  wire:confirm="دسته‌بندی حذف شود؟" wire:click="remove({{ $category->id }})" />
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-base-300 p-6 text-center text-sm opacity-70">هنوز دسته‌بندی ثبت نشده است.</div>
        @endforelse
    </div>

    <x-card title="فهرست دسته‌بندی‌ها">
        <div class="hidden overflow-x-auto md:block">
            <table class="table">
                <thead><tr><th>نام</th><th>شناسه</th><th>وضعیت</th><th>اقدام</th></tr></thead>
                <tbody>
                @forelse($categories as $category)
                    <tr wire:key="category-{{ $category->id }}">
                        <td>{{ $category->name }}</td>
                        <td dir="ltr">{{ $category->slug }}</td>
                        <td>{{ $category->is_active ? 'فعال' : 'غیرفعال' }}</td>
                        <td class="flex gap-2">
                            <x-button label="ویرایش" class="btn-sm" wire:click="edit({{ $category->id }})" />
                            @if(! $category->products()->withTrashed()->exists())
                                <x-button label="حذف" class="btn-sm btn-error btn-outline"
                                          wire:confirm="دسته‌بندی حذف شود؟" wire:click="remove({{ $category->id }})" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">هنوز دسته‌بندی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
