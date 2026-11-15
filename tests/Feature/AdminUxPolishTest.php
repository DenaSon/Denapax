<?php

namespace Tests\Feature;

use App\Livewire\Admin\PaymentReviewPage;
use App\Models\Customer;
use App\Models\GatewayPaymentAttempt;
use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Models\User;
use App\Support\AdminLabels;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUxPolishTest extends TestCase
{
    use RefreshDatabase;

    private User $reviewer;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole('super-admin');

        $customer = Customer::create(['mobile' => '09120000666', 'mobile_verified_at' => now()]);
        $this->order = Order::create([
            'order_number' => 'DN-ADMIN-UX-1',
            'checkout_key' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_status' => 'awaiting_payment',
            'payment_status' => 'under_review',
            'recipient_name' => 'خریدار دنا',
            'recipient_mobile' => '09123456789',
            'province' => 'تهران',
            'city' => 'تهران',
            'address_line' => 'خیابان آزمایش پلاک دوازده',
            'postal_code' => '1234567890',
            'goods_subtotal_irr' => 1200000,
            'shipping_fee_irr' => 300000,
            'total_irr' => 1500000,
            'shipping_weight_grams' => 700,
            'shipping_rule_snapshot' => ['base_weight_grams' => 1000],
            'payment_instructions_snapshot' => ['card_number' => '6037991234567890', 'card_holder_name' => 'دنا'],
        ]);
    }

    public function test_admin_dashboard_shows_actionable_counts_and_persian_order_states(): void
    {
        PaymentReceipt::create([
            'order_id' => $this->order->id,
            'attempt_number' => 1,
            'storage_path' => 'payment-receipts/fixture.png',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        GatewayPaymentAttempt::create([
            'order_id' => $this->order->id,
            'amount_irr' => $this->order->total_irr,
            'status' => 'verification_pending',
            'authority' => 'A'.str_repeat('1', 35),
        ]);

        $this->actingAs($this->reviewer, 'web')->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('داشبورد دناپکس')
            ->assertSee('رسیدهای در انتظار بررسی بانک')
            ->assertSee('تراکنش‌های نامشخص زرین‌پال')
            ->assertSee('در انتظار پرداخت')
            ->assertSee('منوی مدیریت')
            ->assertSee('aria-current="page"', false);

        $this->get(route('admin.orders'))->assertOk()
            ->assertSee('admin-state-warning')
            ->assertSee('در انتظار بررسی رسید')
            ->assertDontSee('<td>under_review</td>', false);

        $this->get(route('admin.payments.gateway'))->assertOk()
            ->assertSee('نتیجه نامشخص')
            ->assertSee('استعلام زنده از زرین‌پال');
    }

    public function test_order_only_staff_cannot_see_financial_review_actions_or_counts(): void
    {
        $staff = User::factory()->create();
        $staff->givePermissionTo('orders.view');

        $this->actingAs($staff, 'web')->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('سفارش‌های در انتظار پرداخت')
            ->assertDontSee('رسیدهای در انتظار بررسی بانک')
            ->assertDontSee('تراکنش‌های نامشخص زرین‌پال')
            ->assertDontSee('تراکنش‌های زرین‌پال');

        $this->get(route('admin.payments'))->assertForbidden();
        $this->get(route('admin.payments.gateway'))->assertForbidden();
    }

    public function test_rejection_form_is_scoped_to_selected_receipt_and_only_reviewers_can_start_it(): void
    {
        $receipt = PaymentReceipt::create([
            'order_id' => $this->order->id,
            'attempt_number' => 1,
            'storage_path' => 'payment-receipts/fixture.png',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->reviewer, 'web');

        Livewire::test(PaymentReviewPage::class)
            ->call('startRejection', $receipt->id)
            ->assertSet('rejectingReceiptId', $receipt->id)
            ->assertSee('دلیل رد این رسید')
            ->call('reject', $receipt->id)
            ->assertHasErrors(['rejectionReason'])
            ->call('cancelRejection')
            ->assertSet('rejectingReceiptId', null);

        $this->assertSame('pending', $receipt->fresh()->status);

        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $this->actingAs($operator, 'web')->get(route('admin.payments'))
            ->assertOk()->assertDontSee('تأیید پس از تطبیق بانک');
        $this->expectException(AuthorizationException::class);
        Livewire::test(PaymentReviewPage::class)->call('startRejection', $receipt->id);
    }

    public function test_ambiguous_gateway_hides_unpaid_cancellation_action_in_order_detail(): void
    {
        $this->order->update(['payment_status' => 'rejected']);
        GatewayPaymentAttempt::create([
            'order_id' => $this->order->id,
            'amount_irr' => $this->order->total_irr,
            'status' => 'verification_pending',
            'authority' => 'A'.str_repeat('2', 35),
        ]);

        $this->actingAs($this->reviewer, 'web')->get(route('admin.orders.show', $this->order))
            ->assertOk()
            ->assertSee('وضعیت یکی از تراکنش‌های درگاه هنوز قطعی نیست')
            ->assertSee('بررسی تراکنش درگاه')
            ->assertDontSee('لغو سفارش بدون پرداخت');

        $this->assertSame('نتیجه نامشخص', AdminLabels::gateway('verification_pending'));
    }

    public function test_notification_report_uses_current_web_language_not_retired_telegram(): void
    {
        $this->actingAs($this->reviewer, 'web')->get(route('admin.notifications'))
            ->assertOk()
            ->assertSee('اعلان‌های سفارش در وب')
            ->assertDontSee('گزارش ارسال اعلان‌های تلگرام');
    }
}
