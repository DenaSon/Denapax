<?php

namespace Tests\Feature;

use App\Livewire\Admin\Catalog\ProductEditor;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCatalogUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin, 'web');
    }

    public function test_catalog_lists_render_mobile_cards_and_keep_desktop_tables(): void
    {
        $category = Category::create([
            'name' => 'خشکبار دنا',
            'slug' => 'dena-nuts',
            'is_active' => true,
        ]);
        Product::create([
            'category_id' => $category->id,
            'title' => 'گردوی دنا',
            'slug' => 'dena-walnut',
            'summary' => 'گردوی محلی',
            'description' => 'توضیحات نمونه',
            'is_active' => false,
        ]);

        $this->get(route('admin.products'))->assertOk()
            ->assertSee('md:hidden', false)
            ->assertSee('hidden overflow-x-auto md:block', false)
            ->assertSee('گردوی دنا')
            ->assertSee('بایگانی محصول');

        $this->get(route('admin.categories'))->assertOk()
            ->assertSee('md:hidden', false)
            ->assertSee('hidden overflow-x-auto md:block', false)
            ->assertSee('خشکبار دنا')
            ->assertSee('dena-nuts');
    }

    public function test_product_gallery_shrinks_by_twenty_percent_only_on_desktop(): void
    {
        $css = file_get_contents(resource_path('css/landing.css'));

        $this->assertStringContainsString(
            "@media (min-width:1024px) {\n    .product-gallery-stage {\n        width:80%;\n        max-width:448px;\n        max-height:448px;",
            $css
        );
        $this->assertStringContainsString(
            ".product-gallery-thumbs {\n        width:80%;\n        max-width:448px;",
            $css
        );
        $this->assertStringContainsString('.product-gallery-stage {', $css);
        $this->assertStringContainsString('aspect-ratio: 1;', $css);
    }

    public function test_product_editor_previews_pending_images_and_exposes_upload_and_save_feedback(): void
    {
        $this->get(route('admin.products.create'))->assertOk()
            ->assertSee('x-on:change="uploadFiles($event)"', false)
            ->assertSee('x-on:change="selectPreview($event)"', false)
            ->assertSee('uploadMultiple(', false)
            ->assertSee('x-for="(file, index) in files"', false)
            ->assertSee('URL.createObjectURL(file)', false)
            ->assertSee('URL.revokeObjectURL', false)
            ->assertSee('x-bind:src="file.url"', false)
            ->assertSee('x-bind:src="previewUrl"', false)
            ->assertSee("file.status === 'uploading' || file.status === 'queued'", false)
            ->assertSee('serverFilename', false)
            ->assertSee('در حال ذخیره محصول…')
            ->assertSee('wire:loading.attr="disabled"', false)
            ->assertDontSee('preview-file/', false);

        Livewire::test(ProductEditor::class)
            ->call('addVariant')
            ->set('galleryUploads', [UploadedFile::fake()->image('gallery.jpg')])
            ->set('variantUploads.0', UploadedFile::fake()->image('variant.jpg'))
            ->assertHasNoErrors()
            ->assertSee('تصویر جدید · در انتظار ذخیره')
            ->assertSee('تصویر انتخاب‌شده')
            ->assertSee('پیش‌نمایش تصویر جدید')
            ->assertDontSee('/preview-file/', false);

        // Temporary preview image elements must use local browser blob URLs,
        // not Livewire signed URLs that can fail behind deployment proxies.
        $view = file_get_contents(resource_path('views/livewire/admin/catalog/product-editor.blade.php'));
        $this->assertStringNotContainsString('->temporaryUrl()', $view);
        $this->assertStringContainsString('await this.$wire.removeGalleryUpload(file.serverFilename)', $view);
        $this->assertStringContainsString('destroy() {', $view);
    }
}
