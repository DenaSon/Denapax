<?php

namespace Tests\Feature;

use App\Application\Analytics\PageViewReport;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPageViewAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_dashboard_displays_today_yesterday_all_time_and_top_product_visits(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-21 12:00:00', 'Asia/Tehran'));

        $category = Category::create([
            'name' => 'خشکبار', 'slug' => 'view-analytics-nuts', 'is_active' => true,
        ]);
        $walnut = Product::create([
            'category_id' => $category->id, 'title' => 'مغز گردو دنا',
            'slug' => 'analytics-walnut', 'summary' => 'توضیح',
            'is_active' => true,
        ]);
        $peach = Product::create([
            'category_id' => $category->id, 'title' => 'هلو خشک',
            'slug' => 'analytics-peach', 'summary' => 'توضیح',
            'is_active' => true,
        ]);

        $today = now('Asia/Tehran')->toDateString();
        $yesterday = now('Asia/Tehran')->subDay()->toDateString();
        $older = now('Asia/Tehran')->subDays(3)->toDateString();

        $this->daily($today, 'home', 0, 10);
        $this->daily($today, 'product', $walnut->id, 5);
        $this->daily($today, 'product', $peach->id, 2);
        $this->daily($yesterday, 'home', 0, 20);
        $this->daily($yesterday, 'product', $walnut->id, 1);
        $this->daily($older, 'product', $peach->id, 10);

        $report = app(PageViewReport::class)->overview();

        $this->assertSame(17, $report['today']);
        $this->assertSame(21, $report['yesterday']);
        $this->assertSame(48, $report['total']);
        $this->assertSame(30, $report['home']);
        $this->assertSame(18, $report['products']);
        $this->assertSame([
            ['id' => $peach->id, 'title' => 'هلو خشک', 'views' => 12],
            ['id' => $walnut->id, 'title' => 'مغز گردو دنا', 'views' => 6],
        ], $report['topProducts']);

        $staff = User::factory()->create();
        $staff->assignRole('super-admin');
        $this->actingAs($staff, 'web');

        $this->get('/admin')->assertOk()
            ->assertSee('آمار بازدید فروشگاه')
            ->assertSee('بازدید امروز')
            ->assertSee('بازدید دیروز')
            ->assertSee('کل بازدیدها')
            ->assertSee('صفحه اصلی')
            ->assertSee('صفحات محصولات')
            ->assertSee('۵ محصول پربازدید')
            ->assertSee('هلو خشک')
            ->assertSee('مغز گردو دنا');
    }

    public function test_empty_analytics_are_rendered_as_zero_without_errors(): void
    {
        $report = app(PageViewReport::class)->overview();

        $this->assertSame(0, $report['today']);
        $this->assertSame(0, $report['yesterday']);
        $this->assertSame(0, $report['total']);
        $this->assertSame(0, $report['home']);
        $this->assertSame(0, $report['products']);
        $this->assertSame([], $report['topProducts']);

        $staff = User::factory()->create();
        $staff->assignRole('super-admin');
        $this->actingAs($staff, 'web');

        $this->get('/admin')->assertOk()
            ->assertSee('هنوز بازدیدی برای محصولات ثبت نشده است.');
    }

    public function test_reports_remain_inside_the_existing_staff_dashboard_authorization(): void
    {
        $this->get('/admin')->assertRedirect();

        $staffWithoutPermission = User::factory()->create();
        $this->actingAs($staffWithoutPermission, 'web');

        $this->get('/admin')->assertForbidden();
    }

    private function daily(string $date, string $type, int $productId, int $views): void
    {
        DB::table('page_view_daily')->insert([
            'view_date' => $date,
            'page_type' => $type,
            'product_id' => $productId,
            'views' => $views,
            'unique_visitors' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
