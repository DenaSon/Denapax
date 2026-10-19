<div class="space-y-5">
    <div class="flex flex-wrap justify-between gap-2">
        <h1 class="text-2xl font-bold">{{ $productId ? 'ویرایش محصول' : 'محصول جدید' }}</h1>
        <a class="btn btn-ghost" href="{{ route('admin.products') }}">بازگشت</a>
    </div>
    <form wire:submit="save" class="space-y-5">
        <x-card title="مشخصات محصول">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="form-control"><span class="label">دسته‌بندی</span>
                    <select wire:model="categoryId" class="select w-full">
                        <option value="0">انتخاب کنید</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('categoryId') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </label>
                <x-input label="نام محصول" wire:model.live.debounce.500ms="title" />
                <x-input label="شناسه انگلیسی (Slug)" wire:model.live="slug" />
                <x-input label="خلاصه محصول" wire:model="summary" />
            </div>
            <label class="form-control mt-4"><span class="label">توضیحات</span>
                <textarea class="textarea w-full" rows="4" wire:model="description"></textarea>
            </label>
            <label class="label mt-4 cursor-pointer justify-start gap-3">
                <input type="checkbox" wire:model="isActive" class="checkbox">
                محصول منتشر شود
            </label>
        </x-card>

        <x-card title="ویژگی‌های عمومی محصول">
            <div class="space-y-3">
                @foreach($features as $index => $feature)
                    <div wire:key="feature-{{ $index }}" class="grid gap-2 md:grid-cols-[1fr_1fr_auto]">
                        <x-input label="عنوان" wire:model="features.{{ $index }}.name" />
                        <x-input label="مقدار" wire:model="features.{{ $index }}.value" />
                        <x-button type="button" label="حذف" wire:click="removeFeature({{ $index }})" class="btn-error btn-outline self-end" />
                    </div>
                @endforeach
                <x-button type="button" label="افزودن ویژگی" wire:click="addFeature" />
            </div>
        </x-card>

        <x-card title="تنوع‌های فروش">
            <p class="mb-3 text-sm opacity-70">برای نمایش در بات، حداقل یک تنوع «قابل سفارش» لازم است. قیمت‌ها را به تومان وارد کنید.</p>
            @error('variants') <p class="text-error mb-2">{{ $message }}</p> @enderror
            <div class="space-y-4">
                @foreach($variants as $index => $variant)
                    <div class="rounded-lg border border-base-300 p-4" wire:key="variant-{{ $index }}">
                        <div class="grid gap-3 md:grid-cols-3">
                            <x-input label="شناسه SKU (پیشنهاد خودکار)" wire:model="variants.{{ $index }}.sku" />
                            <x-input label="عنوان تنوع" wire:model="variants.{{ $index }}.title" />
                            <x-input label="مقدار فروش" type="number" min="0.001" step="0.001" wire:model="variants.{{ $index }}.sale_quantity" />
                            <label class="form-control"><span class="label">واحد فروش</span>
                                <select class="select w-full" wire:model="variants.{{ $index }}.sale_unit">
                                    <option value="g">گرم</option><option value="kg">کیلوگرم</option>
                                    <option value="l">لیتر</option><option value="piece">عدد</option>
                                </select>
                            </label>
                            <div>
                                <x-input label="قیمت (تومان)" type="text" inputmode="decimal" dir="ltr" wire:model="variants.{{ $index }}.price_toman" />
                                <p class="mt-1 text-xs opacity-70">مثال: 1389000؛ جداکننده هزارگان اختیاری است.</p>
                            </div>
                            <x-input label="وزن پستی (گرم)" type="number" min="1" wire:model="variants.{{ $index }}.shipping_weight_grams" />
                        </div>
                        <label class="label mt-2 cursor-pointer justify-start gap-3">
                            <input type="checkbox" class="checkbox" wire:model="variants.{{ $index }}.is_available"> قابل سفارش
                        </label>
                        <label class="form-control mt-3">
                            <span class="label">تصویر اختصاصی (اختیاری)</span>
                            <input type="file" wire:model="variantUploads.{{ $index }}" accept="image/jpeg,image/png,image/webp" class="file-input w-full">
                        </label>
                        @if($product && !empty($variant['id']))
                            @php($savedVariant = $product->variants->firstWhere('id', $variant['id']))
                            @if($savedVariant && $savedVariant->getFirstMediaUrl('variant-image'))
                                <img src="{{ $savedVariant->getFirstMediaUrl('variant-image') }}" alt="" class="mt-2 h-20 w-20 rounded object-cover">
                            @endif
                        @endif
                        <x-button type="button" label="{{ !empty($variant['id']) ? 'غیرفعال‌کردن تنوع' : 'حذف ردیف' }}" wire:click="removeVariant({{ $index }})" class="btn-error btn-outline mt-3" />
                        @foreach($errors->get('variants.'.$index.'.*') as $messages)
                            @foreach($messages as $message)
                                <p class="text-error text-sm">{{ $message }}</p>
                            @endforeach
                        @endforeach
                    </div>
                @endforeach
                <x-button type="button" label="افزودن تنوع" wire:click="addVariant" />
            </div>
        </x-card>

        <x-card title="گالری تصاویر محصول">
            @if($product)
                <div class="mb-3 flex flex-wrap gap-3">
                    @foreach($product->getMedia('gallery') as $media)
                        <div class="space-y-2" wire:key="media-{{ $media->id }}">
                            <img src="{{ $media->getUrl() }}" alt="{{ $product->title }}" class="h-24 w-24 rounded object-cover">
                            <x-button type="button" label="حذف تصویر" class="btn-error btn-sm" wire:click="removeGalleryImage({{ $media->id }})" />
                        </div>
                    @endforeach
                </div>
            @endif
            <input type="file" wire:model="galleryUploads" multiple accept="image/jpeg,image/png,image/webp" class="file-input w-full">
            @error('galleryUploads.*') <p class="text-error text-sm">{{ $message }}</p> @enderror
        </x-card>
        <div class="flex justify-end">
            <x-button type="submit" label="ذخیره محصول" class="btn-primary btn-lg" />
        </div>
    </form>
</div>
