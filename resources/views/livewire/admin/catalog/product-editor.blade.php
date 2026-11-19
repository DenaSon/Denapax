<div class="space-y-5">
    <div class="flex flex-wrap justify-between gap-2">
        <h1 class="text-2xl font-bold">{{ $productId ? 'ویرایش محصول' : 'محصول جدید' }}</h1>
        <a wire:navigate class="btn btn-ghost" href="{{ route('admin.products') }}">بازگشت</a>
    </div>
    <form wire:submit="save" class="space-y-5">
        @if ($errors->any())
            <div role="alert" class="alert alert-error" tabindex="-1">
                <div>
                    <p class="font-bold">ذخیره محصول انجام نشد؛ موارد زیر را بررسی کنید:</p>
                    <ul class="mt-2 list-disc space-y-1 pr-5 text-sm">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
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
                        <div class="mt-3"
                             x-data="{
                                 uploading: false,
                                 progress: 0,
                                 previewUrl: null,
                                 previewError: false,
                                 selectPreview(event) {
                                     this.clearPreview();
                                     this.previewError = false;
                                     const file = event.target.files?.[0];
                                     if (file && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                                         this.previewUrl = URL.createObjectURL(file);
                                     }
                                 },
                                 clearPreview() {
                                     if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                                     this.previewUrl = null;
                                 },
                                 destroy() { this.clearPreview(); },
                             }"
                             x-on:livewire-upload-start="uploading = true; progress = 0"
                             x-on:livewire-upload-finish="uploading = false; progress = 100"
                             x-on:livewire-upload-error="uploading = false; clearPreview(); previewError = true"
                             x-on:livewire-upload-cancel="uploading = false; clearPreview()"
                             x-on:livewire-upload-progress="progress = $event.detail.progress">
                            <label class="form-control">
                                <span class="label">تصویر اختصاصی (اختیاری)</span>
                                <input type="file" wire:model="variantUploads.{{ $index }}" x-on:change="selectPreview($event)" accept="image/jpeg,image/png,image/webp" class="file-input w-full">
                            </label>
                            <div x-show="uploading" x-cloak class="mt-2 space-y-1" role="status" aria-live="polite">
                                <div class="flex justify-between text-xs"><span>در حال بارگذاری تصویر تنوع…</span><span x-text="Math.round(progress) + '%'"></span></div>
                                <progress class="progress progress-primary w-full" max="100" x-bind:value="progress"></progress>
                            </div>
                            <p x-show="previewError" x-cloak class="mt-2 text-xs text-error" role="alert">بارگذاری تصویر انجام نشد؛ دوباره تلاش کنید.</p>
                            <div x-show="previewUrl" x-cloak class="mt-3 flex items-center gap-3 rounded-xl border border-dashed border-primary/40 p-3">
                                <img x-bind:src="previewUrl" alt="پیش‌نمایش تصویر انتخاب‌شده" class="h-20 w-20 rounded-lg object-cover">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold">تصویر انتخاب‌شده</p>
                                    <p class="text-xs opacity-70">پس از ذخیره محصول اضافه می‌شود.</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-error btn-outline"
                                        x-bind:disabled="uploading"
                                        x-on:click="clearPreview(); $wire.removeVariantUpload({{ $index }})">حذف</button>
                            </div>
                            @if (isset($variantUploads[$index]))
                                <div x-show="!previewUrl" x-cloak class="mt-3 flex items-center gap-3 rounded-xl border border-dashed border-primary/40 p-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold">تصویر انتخاب‌شده · آماده ذخیره</p>
                                    </div>
                                    <x-button type="button" label="حذف" class="btn-sm btn-error btn-outline"
                                              wire:click="removeVariantUpload({{ $index }})" />
                                </div>
                            @endif
                        </div>
                        @if($product && !empty($variant['id']))
                            @php
                                $savedVariant = $product->variants->firstWhere('id', $variant['id']);
                                $dedicatedMedia = $savedVariant?->getFirstMedia('variant-image');
                                $variantMedia = $savedVariant
                                    ? app(\App\Support\ProductImageResolver::class)->forVariant($savedVariant, $product)
                                    : null;
                            @endphp
                            @if($variantMedia)
                                <div class="mt-2 flex items-center gap-2">
                                    <img src="{{ $variantMedia->hasGeneratedConversion('product-thumb') ? $variantMedia->getUrl('product-thumb') : $variantMedia->getUrl() }}"
                                         alt="{{ $product->title }}" class="h-16 w-16 rounded-lg object-cover">
                                    <span class="text-xs opacity-70">{{ $dedicatedMedia ? 'تصویر اختصاصی این تنوع' : 'تصویر مشترک محصول (بدون نیاز به آپلود مجدد)' }}</span>
                                </div>
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
                            <img src="{{ $media->hasGeneratedConversion('product-thumb') ? $media->getUrl('product-thumb') : $media->getUrl() }}" alt="{{ $product->title }}" class="h-24 w-24 rounded object-contain">
                            <x-button type="button" label="حذف تصویر" class="btn-error btn-sm" wire:click="removeGalleryImage({{ $media->id }})" />
                        </div>
                    @endforeach
                </div>
            @endif
            <div x-data="{
                    files: [],
                    nextId: 0,
                    uploadFiles(event) {
                        const selected = Array.from(event.target.files).filter(
                            (file) => ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
                        );
                        if (!selected.length) return;

                        const batch = selected.map((file) => ({
                            id: this.nextId++,
                            name: file.name,
                            url: URL.createObjectURL(file),
                            progress: 0,
                            status: 'queued',
                            serverFilename: null,
                        }));
                        this.files.push(...batch);

                        const uploadNext = (index) => {
                            if (index >= selected.length) return;

                            const preview = batch[index];
                            preview.status = 'uploading';
                            this.$wire.uploadMultiple(
                                'galleryUploads',
                                [selected[index]],
                                (uploadedFilenames) => {
                                    preview.serverFilename = Array.isArray(uploadedFilenames)
                                        ? uploadedFilenames[0] : uploadedFilenames;
                                    preview.progress = 100;
                                    preview.status = 'complete';
                                    uploadNext(index + 1);
                                },
                                () => {
                                    preview.status = 'error';
                                    uploadNext(index + 1);
                                },
                                (progressEvent) => {
                                    preview.progress = progressEvent.detail.progress;
                                },
                            );
                        };

                        uploadNext(0);
                        event.target.value = '';
                    },
                    async removeFile(index) {
                        const file = this.files[index];
                        if (!file || file.status === 'uploading' || file.status === 'queued') return;

                        if (file.serverFilename) {
                            try {
                                await this.$wire.removeGalleryUpload(file.serverFilename);
                            } catch (error) {
                                file.status = 'remove-error';
                                return;
                            }
                        }

                        URL.revokeObjectURL(file.url);
                        this.files.splice(index, 1);
                    },
                    destroy() {
                        this.files.forEach((file) => URL.revokeObjectURL(file.url));
                    },
                }">
                <input type="file" multiple accept="image/jpeg,image/png,image/webp"
                       x-on:change="uploadFiles($event)"
                       x-bind:disabled="files.some((file) => file.status === 'uploading' || file.status === 'queued')"
                       class="file-input w-full">
                <div x-show="files.length > 0" x-cloak class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" role="status" aria-live="polite">
                    <template x-for="(file, index) in files" :key="file.id">
                        <div class="rounded-xl border border-dashed border-primary/40 p-2">
                            <img x-bind:src="file.url" alt="پیش‌نمایش تصویر جدید" class="aspect-square w-full rounded-lg object-cover">
                            <p class="mt-2 break-all text-xs font-semibold" x-text="file.name"></p>
                            <div class="mt-2 flex items-center justify-between gap-2 text-xs">
                                <span x-text="file.status === 'complete' ? 'تصویر جدید · در انتظار ذخیره' : (file.status === 'error' ? 'خطا در بارگذاری' : (file.status === 'remove-error' ? 'خطا در حذف' : Math.round(file.progress) + '%'))"></span>
                            </div>
                            <progress x-show="file.status === 'uploading'" class="progress progress-primary mt-2 w-full" max="100" x-bind:value="file.progress"></progress>
                            <button type="button" class="btn btn-error btn-outline btn-sm mt-2 w-full"
                                    x-bind:disabled="file.status === 'uploading' || file.status === 'queued'"
                                    x-on:click="removeFile(index)">حذف تصویر</button>
                        </div>
                    </template>
                </div>
                @if (!empty($galleryUploads))
                    <div x-show="files.length === 0" x-cloak class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($galleryUploads as $upload)
                            <div wire:key="pending-gallery-{{ $upload->getFilename() }}" class="rounded-xl border border-dashed border-primary/40 p-2">
                                <p class="break-all text-xs font-semibold">{{ $upload->getClientOriginalName() }}</p>
                                <p class="mt-2 text-xs opacity-70">تصویر جدید · آماده ذخیره</p>
                                <x-button type="button" label="حذف تصویر" class="btn-error btn-outline btn-sm mt-2 w-full"
                                          wire:click="removeGalleryUpload('{{ $upload->getFilename() }}')" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @error('galleryUploads.*') <p class="text-error text-sm">{{ $message }}</p> @enderror
        </x-card>
        <div class="flex flex-wrap items-center justify-end gap-3">
            <span wire:loading wire:target="save" role="status" class="text-sm font-semibold text-base-content/70">
                در حال ذخیره محصول…
            </span>
            <x-button type="submit" label="ذخیره محصول" class="btn-primary btn-lg"
                      wire:loading.attr="disabled" wire:target="save,galleryUploads,variantUploads" />
        </div>
    </form>
</div>
