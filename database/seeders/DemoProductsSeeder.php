<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoProductsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'staging'])
            || ! in_array(config('app.env'), ['local', 'testing', 'staging'], true)) {
            throw new RuntimeException('DemoProductsSeeder is restricted to local, testing and staging environments.');
        }

        // Opt-in fixtures: not called by DatabaseSeeder, never published
        // or offered for sale until a staff member explicitly reviews them.
        $category = Category::firstOrCreate(
            ['slug' => 'denapax-demo-dry-fruits'],
            ['name' => 'خشکبار نمایشی دنا', 'is_active' => false, 'sort_order' => 900],
        );

        $samples = [
            [
                'slug' => 'denapax-demo-dried-figs',
                'title' => 'انجیر خشک نمونه',
                'summary' => 'محصول نمایشی برای بررسی ظاهر فروشگاه؛ اطلاعات و قیمت واقعی نیست.',
                'sku' => 'DENAPAX-DEMO-FIG-500',
                'price_irr' => 1950000,
                'shipping_weight_grams' => 550,
            ],
            [
                'slug' => 'denapax-demo-walnuts',
                'title' => 'مغز گردو نمونه',
                'summary' => 'محصول نمایشی برای بررسی ظاهر فروشگاه؛ اطلاعات و قیمت واقعی نیست.',
                'sku' => 'DENAPAX-DEMO-WALNUT-500',
                'price_irr' => 2850000,
                'shipping_weight_grams' => 550,
            ],
            [
                'slug' => 'denapax-demo-raisins',
                'title' => 'کشمش نمونه',
                'summary' => 'محصول نمایشی برای بررسی ظاهر فروشگاه؛ اطلاعات و قیمت واقعی نیست.',
                'sku' => 'DENAPAX-DEMO-RAISIN-500',
                'price_irr' => 1250000,
                'shipping_weight_grams' => 550,
            ],
        ];

        foreach ($samples as $position => $sample) {
            $product = Product::firstOrCreate(
                ['slug' => $sample['slug']],
                [
                    'category_id' => $category->id,
                    'title' => $sample['title'],
                    'summary' => $sample['summary'],
                    'description' => 'صرفاً داده آزمایشی؛ پیش از فعال‌سازی، مشخصات، موجودی و قیمت را بررسی کنید.',
                    'is_active' => false,
                ],
            );

            ProductVariant::firstOrCreate(
                ['sku' => $sample['sku']],
                [
                    'product_id' => $product->id,
                    'title' => 'بسته ۵۰۰ گرمی (نمونه)',
                    'sale_quantity' => 500,
                    'sale_unit' => 'g',
                    'price_irr' => $sample['price_irr'],
                    'shipping_weight_grams' => $sample['shipping_weight_grams'],
                    'is_available' => false,
                    'sort_order' => $position,
                ],
            );
        }
    }
}
