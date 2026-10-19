<?php

namespace Tests\Feature;

use App\Application\Notifications\TelegramNotificationText;
use App\Integrations\Telegram\CartCheckoutMessages;
use App\Integrations\Telegram\CustomerOrdersBot;
use App\Integrations\Telegram\OrderStateLabels;
use App\Integrations\Telegram\ReceiptBot;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentReceipt;
use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\TestCase;

class TelegramCheckoutStatusUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_replay_shows_payment_steps_only_while_unpaid(): void
    {
        $owner = Customer::create(['telegram_user_id' => 4512]);
        $order = $this->order($owner);
        $messages = app(CartCheckoutMessages::class);

        $bot = $this->bot();
        $messages->confirm($bot, $owner, $order->checkout_key);
        $text = $this->latestMessage($bot);
        $this->assertStringContainsString('در انتظار پرداخت', $text['text']);
        $this->assertStringContainsString('120,000 تومان', $text['text']);
        $this->assertStringContainsString('شماره کارت: <code>6037991234567890</code>', $text['text']);
        $this->assertSame('HTML', $text['parse_mode']);
        $this->assertStringContainsString('<b>💳 مبلغ قابل پرداخت: 120,000 تومان</b>', $text['text']);
        $this->assertStringContainsString('<blockquote>پس از بررسی و تأیید پرداخت', $text['text']);
        $this->assertStringNotContainsString('به‌معنای تأیید پرداخت نیست', $text['text']);
        $buttons = collect($text['reply_markup']['inline_keyboard'])->flatten(1);
        $this->assertTrue($buttons->contains(fn ($button) => ($button['callback_data'] ?? '') === 'receipt:order:'.$order->id));
        $this->assertTrue($buttons->contains(fn ($button) => ($button['callback_data'] ?? '') === 'myorder:'.$order->id));
        $this->assertTrue($buttons->contains(fn ($button) => ($button['copy_text']['text'] ?? '') === '6037991234567890'));
        $this->assertStringContainsString($order->public_reference, $text['text']);

        $order->update(['payment_status' => 'approved', 'order_status' => 'confirmed']);
        $bot = $this->bot();
        $messages->confirm($bot, $owner, $order->checkout_key);
        $after = $this->latestMessage($bot);
        $this->assertStringContainsString('در صف آماده‌سازی', $after['text']);
        $this->assertStringNotContainsString('شماره کارت:', $after['text']);
        $this->assertCount(2, $after['reply_markup']['inline_keyboard']);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_receipt_order_selection_and_rejection_are_owned_and_clear(): void
    {
        $owner = Customer::create(['telegram_user_id' => 4512]);
        $other = Customer::create(['telegram_user_id' => 9999]);
        $order = $this->order($owner);
        $receiptFlow = app(ReceiptBot::class);
        $bot = $this->bot();

        $receiptFlow->orders($bot, $owner);
        $list = $this->latestMessage($bot);
        $this->assertStringContainsString('ابتدا سفارش موردنظر را انتخاب', $list['text']);
        $this->assertStringContainsString('120,000 تومان', $list['reply_markup']['inline_keyboard'][0][0]['text']);

        $bot = $this->bot();
        $receiptFlow->order($bot, $other, $order->id);
        $this->assertStringContainsString('در حساب شما پیدا نشد', $this->latestMessage($bot)['text']);

        $order->update(['payment_status' => 'rejected']);
        PaymentReceipt::create([
            'order_id' => $order->id,
            'attempt_number' => 1,
            'storage_path' => 'payment-receipts/test.png',
            'status' => 'rejected',
            'rejection_reason' => 'مبلغ تطبیق نداشت',
            'submitted_at' => now(),
        ]);

        $bot = $this->bot();
        $receiptFlow->order($bot, $owner, $order->id);
        $detail = $this->latestMessage($bot);
        $this->assertStringContainsString('مبلغ تطبیق نداشت', $detail['text']);
        $this->assertStringContainsString('همین سفارش رسید جدید', $detail['text']);
        $this->assertTrue(collect($detail['reply_markup']['inline_keyboard'])->flatten(1)
            ->contains(fn ($button) => ($button['callback_data'] ?? '') === 'receipt:upload:'.$order->id));

        $order->update(['payment_status' => 'under_review']);
        $bot = $this->bot();
        $receiptFlow->order($bot, $owner, $order->id);
        $waiting = $this->latestMessage($bot);
        $this->assertStringContainsString('در انتظار بررسی', $waiting['text']);
        $this->assertFalse(collect($waiting['reply_markup']['inline_keyboard'])->flatten(1)
            ->contains(fn ($button) => str_starts_with($button['callback_data'] ?? '', 'receipt:upload:')));
    }

    public function test_order_detail_explains_approved_vs_preparing_and_shipping(): void
    {
        $owner = Customer::create(['telegram_user_id' => 4512]);
        $other = Customer::create(['telegram_user_id' => 9999]);
        $order = $this->order($owner);
        $screen = app(CustomerOrdersBot::class);

        $bot = $this->bot();
        $screen->details($bot, $other, $order->id);
        $this->assertStringContainsString('در حساب شما پیدا نشد', $this->latestMessage($bot)['text']);

        $order->update(['order_status' => 'confirmed', 'payment_status' => 'approved']);
        $bot = $this->bot();
        $screen->details($bot, $owner, $order->id);
        $confirmed = $this->latestMessage($bot);
        $this->assertStringContainsString('در صف آماده‌سازی', $confirmed['text']);
        $this->assertStringNotContainsString('در حال آماده‌سازی', $confirmed['text']);
        $this->assertStringContainsString('20,000 تومان', $confirmed['text']);

        $order->update(['order_status' => 'shipped']);
        $order->shipment()->create(['carrier' => 'پست', 'tracking_code' => 'TRACK-001']);
        $bot = $this->bot();
        $screen->details($bot, $owner, $order->id);
        $shipped = $this->latestMessage($bot);
        $this->assertStringContainsString('TRACK-001', $shipped['text']);
        $this->assertStringContainsString('ارسال‌شده', $shipped['text']);
    }

    public function test_all_eight_notifications_preserve_distinct_financial_and_fulfillment_states(): void
    {
        $owner = Customer::create(['telegram_user_id' => 4512]);
        $order = $this->order($owner);
        $notice = app(TelegramNotificationText::class);
        $receipt = new PaymentReceipt(['rejection_reason' => 'رسید ناخواناست']);

        $created = $notice->render('order.created', $order);
        $this->assertStringContainsString('در انتظار پرداخت', $created);
        $this->assertStringContainsString('120,000 تومان', $created);
        $this->assertStringContainsString('ابتدا همین سفارش را انتخاب', $created);
        $this->assertStringContainsString('<blockquote>', $created);
        $this->assertStringContainsString('<b>💳 مبلغ قابل پرداخت: 120,000 تومان</b>', $created);

        $submitted = $notice->render('payment.receipt_submitted', $order);
        $this->assertStringContainsString('در انتظار بررسی', $submitted);
        $this->assertStringNotContainsString('به‌معنای تأیید پرداخت نیست', $submitted);
        $rejected = $notice->render('payment.receipt_rejected', $order, $receipt);
        $this->assertStringContainsString('رسید ناخواناست', $rejected);
        $this->assertStringContainsString('همین سفارش را انتخاب', $rejected);

        $approved = $notice->render('payment.approved', $order);
        $this->assertStringContainsString('در صف آماده‌سازی', $approved);
        $this->assertStringNotContainsString('آماده‌سازی آغاز', $approved);
        $preparing = $notice->render('order.preparing', $order);
        $this->assertStringContainsString('آماده‌سازی', $preparing);

        $order->shipment()->create(['carrier' => 'پست', 'tracking_code' => 'TRACK-002']);
        $this->assertStringContainsString('TRACK-002', $notice->render('order.shipped', $order->fresh()));
        $this->assertStringContainsString('تحویل', $notice->render('order.delivered', $order));
        $this->assertStringContainsString('لغو', $notice->render('order.canceled', $order));

        $this->assertSame('در انتظار پرداخت', OrderStateLabels::order('awaiting_payment'));
        $this->assertSame('رسید دریافت شد؛ در انتظار بررسی', OrderStateLabels::payment('under_review'));
    }

    public function test_payment_and_receipt_text_escape_dynamic_store_values(): void
    {
        $owner = Customer::create(['telegram_user_id' => 4512]);
        $order = $this->order($owner);
        $order->update(['payment_instructions_snapshot' => [
            'card_number' => '6037991234567890',
            'card_holder_name' => 'Dena <Store> & Co',
        ]]);

        $bot = $this->bot();
        app(CartCheckoutMessages::class)->confirm($bot, $owner, $order->checkout_key);
        $sent = $this->latestMessage($bot);
        $this->assertStringContainsString('Dena &lt;Store&gt; &amp; Co', $sent['text']);
        $this->assertStringNotContainsString('Dena <Store>', $sent['text']);

        $notice = app(TelegramNotificationText::class)->render('order.created', $order);
        $this->assertStringContainsString('Dena &lt;Store&gt; &amp; Co', $notice);
    }


    public function test_public_reference_is_stable_unique_and_reversible_without_mutating_legacy_number(): void
    {
        $owner = Customer::create(['telegram_user_id' => 4512]);
        $one = $this->order($owner);
        $two = $this->order($owner);
        $two->update(['order_number' => 'OLD-LEGACY-ORDER-2']);

        $this->assertMatchesRegularExpression('/^DN-[0-9]{9}$/', $one->public_reference);
        $this->assertNotSame($one->public_reference, $two->public_reference);
        $this->assertSame($one->id, \App\Support\OrderReference::toId($one->public_reference));
        $this->assertSame($two->id, \App\Support\OrderReference::toId($two->public_reference));
        $this->assertSame('DN-UX4-001', $one->fresh()->order_number);

        $screen = app(CustomerOrdersBot::class);
        $bot = $this->bot();
        $screen->list($bot, $owner);
        $list = $this->latestMessage($bot);
        $this->assertStringContainsString($one->public_reference, $list['text']);
        $this->assertStringContainsString('120,000 تومان', $list['text']);
        $this->assertStringNotContainsString('DN-UX4-001', $list['text']);
        $this->assertSame('HTML', $list['parse_mode']);
    }

    private function bot(): FakeNutgram
    {
        /** @var FakeNutgram $bot */
        $bot = app(Nutgram::class);
        $bot->setCommonUser(User::make(4512, false, 'Buyer'));
        $bot->setCommonChat(Chat::make(4512, 'private'));

        return $bot;
    }

    private function latestMessage(FakeNutgram $bot): array
    {
        $requests = collect($bot->getRequestHistory())
            ->map(fn ($record) => array_values($record)[0])
            ->filter(fn (Request $request) => $request->getUri()->getPath() === 'sendMessage')
            ->values();
        $this->assertNotEmpty($requests);

        return json_decode((string) $requests->last()->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function order(Customer $owner): Order
    {
        return Order::create([
            'order_number' => 'DN-UX4-'.str_pad((string) (Order::count() + 1), 3, '0', STR_PAD_LEFT),
            'checkout_key' => (string) Str::uuid(),
            'customer_id' => $owner->id,
            'order_status' => 'awaiting_payment',
            'payment_status' => 'awaiting_receipt',
            'recipient_name' => 'Test buyer',
            'recipient_mobile' => '09123456789',
            'province' => 'Tehran',
            'city' => 'Tehran',
            'address_line' => 'Sample long address',
            'postal_code' => '1234567890',
            'goods_subtotal_irr' => 1000000,
            'shipping_fee_irr' => 200000,
            'total_irr' => 1200000,
            'shipping_weight_grams' => 500,
            'shipping_rule_snapshot' => ['base_weight_grams' => 1000],
            'payment_instructions_snapshot' => ['card_number' => '6037991234567890', 'card_holder_name' => 'Dena'],
        ]);
    }
}
