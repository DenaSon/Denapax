<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PageViewAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const VISITOR_A = '0123456789abcdef0123456789abcdef';

    private const VISITOR_B = 'fedcba9876543210fedcba9876543210';

    public function test_public_html_and_product_prefetch_requests_do_not_count_without_a_display_beacon(): void
    {
        $product = $this->product();

        $this->get('/')->assertOk()
            ->assertSee('data-analytics-page="home"', false)
            ->assertSee('data-analytics-endpoint="'.route('analytics.page-view').'"', false)
            ->assertSee('name="csrf-token"', false);
        $this->get(route('products.show', $product->slug))->assertOk()
            ->assertSee('data-analytics-page="product"', false)
            ->assertSee('data-analytics-product-id="'.$product->id.'"', false);

        $this->assertDatabaseCount('page_view_daily', 0);
        $this->assertDatabaseCount('page_view_visitors', 0);

        $js = file_get_contents(resource_path('js/page-views.js'));
        $this->assertStringContainsString("document.addEventListener('livewire:navigated'", $js);
        $this->assertStringContainsString('DOMContentLoaded', $js);
        $this->assertStringNotContainsString("document.addEventListener('livewire:navigate',", $js);
        $this->assertStringContainsString("import './page-views';", file_get_contents(resource_path('js/storefront.js')));
    }

    public function test_duplicate_beacons_are_deduplicated_but_real_repeat_views_are_counted(): void
    {
        $this->beacon('home', 0, self::VISITOR_A)->assertNoContent();
        $this->beacon('home', 0, self::VISITOR_A)->assertNoContent();
        $this->beacon('home', 0, self::VISITOR_B)->assertNoContent();

        $this->travel(11)->seconds();

        $this->beacon('home', 0, self::VISITOR_A)->assertNoContent();

        $stats = DB::table('page_view_daily')->where('page_type', 'home')->first();
        $this->assertNotNull($stats);
        $this->assertSame(3, (int) $stats->views);
        $this->assertSame(2, (int) $stats->unique_visitors);
        $this->assertDatabaseCount('page_view_visitors', 2);
    }

    public function test_product_views_are_separate_and_use_real_sellable_product_ids(): void
    {
        $product = $this->product();
        $this->beacon('home', 0, self::VISITOR_A)->assertNoContent();
        $this->beacon('product', $product->id, self::VISITOR_A)->assertNoContent();

        $this->assertDatabaseHas('page_view_daily', [
            'page_type' => 'home', 'product_id' => 0, 'views' => 1, 'unique_visitors' => 1,
        ]);
        $this->assertDatabaseHas('page_view_daily', [
            'page_type' => 'product', 'product_id' => $product->id, 'views' => 1, 'unique_visitors' => 1,
        ]);

        $product->update(['is_active' => false]);
        $this->beacon('product', $product->id, self::VISITOR_B)->assertNoContent();
        $this->beacon('product', 999999, self::VISITOR_B)->assertNoContent();

        $this->assertDatabaseCount('page_view_daily', 2);
        $this->assertDatabaseCount('page_view_visitors', 2);
    }

    public function test_malformed_payloads_and_known_bots_cannot_inflate_the_counter(): void
    {
        $this->beacon('home', 99, self::VISITOR_A)->assertStatus(422);
        $this->beacon('product', 0, self::VISITOR_A)->assertNoContent();
        $this->beacon('home', 0, 'not-a-valid-token')->assertStatus(422);
        $this->withHeader('User-Agent', 'Googlebot/2.1')
            ->beacon('home', 0, self::VISITOR_A)->assertNoContent();

        $this->assertDatabaseCount('page_view_daily', 0);
        $this->assertDatabaseCount('page_view_visitors', 0);
    }

    public function test_day_boundaries_follow_tehran_not_utc(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-21 20:29:00', 'UTC'));

        $this->beacon('home', 0, self::VISITOR_A)->assertNoContent();
        $this->travel(2)->minutes();
        $this->beacon('home', 0, self::VISITOR_A)->assertNoContent();

        $this->assertDatabaseHas('page_view_daily', [
            'view_date' => '2026-11-21', 'page_type' => 'home', 'views' => 1,
        ]);
        $this->assertDatabaseHas('page_view_daily', [
            'view_date' => '2026-11-22', 'page_type' => 'home', 'views' => 1,
        ]);
        $this->assertDatabaseCount('page_view_daily', 2);
    }

    public function test_pruning_old_anonymous_ids_keeps_historical_counts(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-07-01 12:00:00', 'UTC'));
        $this->beacon('home', 0, self::VISITOR_A)->assertNoContent();
        $this->travel(92)->days();

        $this->artisan('denapax:analytics:prune')->assertExitCode(0);

        $this->assertDatabaseCount('page_view_visitors', 0);
        $this->assertDatabaseCount('page_view_daily', 1);
        $this->assertSame(1, (int) DB::table('page_view_daily')->value('views'));
    }

    private function beacon(string $type, int $id, string $visitor)
    {
        return $this->postJson(route('analytics.page-view'), [
            'page_type' => $type,
            'product_id' => $id,
            'visitor_id' => $visitor,
        ]);
    }

    private function product(): Product
    {
        $category = Category::create([
            'name' => 'خشکبار', 'slug' => 'analytics-nuts', 'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'مغز گردو دنا',
            'slug' => 'analytics-walnut',
            'summary' => 'مغز گردو تازه',
            'is_active' => true,
        ]);

        $product->variants()->create([
            'sku' => 'ANALYTICS-WALNUT-500',
            'title' => 'نیم کیلو',
            'sale_quantity' => 0.5,
            'sale_unit' => 'kg',
            'price_irr' => 600000,
            'shipping_weight_grams' => 550,
            'is_available' => true,
        ]);

        return $product;
    }
}
