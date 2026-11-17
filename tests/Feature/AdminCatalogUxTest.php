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

    public function test_product_editor_previews_pending_images_and_exposes_upload_and_save_feedback(): void
    {
        $this->get(route('admin.products.create'))->assertOk()
            ->assertSee('livewire-upload-progress', false)
            ->assertSee('uploadMultiple(', false)
            ->assertSee('x-for="(file, index) in files"', false)
            ->assertSee('بارگذاری شد')
            ->assertSee('در حال ذخیره محصول…')
            ->assertSee('wire:loading.attr="disabled"', false);

        Livewire::test(ProductEditor::class)
            ->call('addVariant')
            ->set('galleryUploads', [UploadedFile::fake()->image('gallery.jpg')])
            ->set('variantUploads.0', UploadedFile::fake()->image('variant.jpg'))
            ->assertHasNoErrors()
            ->assertSee('تصویر جدید · در انتظار ذخیره')
            ->assertSee('تصویر انتخاب‌شده')
            ->assertSee('پیش‌نمایش تصویر جدید');
    }
}
