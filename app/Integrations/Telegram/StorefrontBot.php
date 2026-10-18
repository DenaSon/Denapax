<?php

namespace App\Integrations\Telegram;

use App\Application\Actions\AddressConversation;
use App\Application\Actions\IdentifyTelegramCustomer;
use App\Application\Actions\ManageCart;
use App\Application\Actions\SaveCustomerAddress;
use App\Models\Customer;
use App\Models\StoreSetting;
use Illuminate\Validation\ValidationException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton as Button;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup as Keyboard;

class StorefrontBot
{
    public function __construct(
        private readonly IdentifyTelegramCustomer $identify,
        private readonly AddressConversation $addresses,
        private readonly SaveCustomerAddress $saveAddress,
        private readonly CatalogMessages $catalog,
        private readonly ManageCart $carts,
        private readonly CartCheckoutMessages $cartCheckout,
        private readonly ReceiptBot $receipts,
    ) {}

    public function onMessage(Nutgram $bot): void
    {
        $message = $bot->message();
        $from = $message?->from;
        $chat = $message?->chat;

        if (! $from || $from->is_bot || ! $chat || $chat->type !== 'private') {
            return;
        }

        $customer = $this->identify->execute(
            $from->id,
            $chat->id,
            trim($from->first_name.' '.($from->last_name ?? '')),
        );

        $text = trim((string) ($message->text ?? ''));

        if ($text === '/start' || str_starts_with($text, '/start@') || $text === '/cancel') {
            $this->addresses->reset($customer);
            $this->menu($bot);

            return;
        }

        if ($text === '/catalog') {
            $this->catalog->categories($bot);

            return;
        }

        if ($text === '/search') {
            $this->startSearch($bot, $customer);

            return;
        }

        if ($text === '/cart') {
            $this->cartCheckout->show($bot, $customer);

            return;
        }

        if ($text === '/checkout') {
            $this->cartCheckout->chooseAddress($bot, $customer);

            return;
        }

        if ($text === '/receipt') {
            $this->receipts->orders($bot, $customer);

            return;
        }

        if ($text === '/addresses') {
            $this->listAddresses($bot, $customer);

            return;
        }

        if ($text === '/support') {
            $this->support($bot);

            return;
        }

        $session = $this->addresses->session($customer);

        if ($session->state === 'receipt:upload') {
            $this->receipts->handle($bot, $customer);

            return;
        }

        if ($this->addresses->isCollecting($session)) {
            $bot->sendMessage($this->addresses->accept($customer, $text, $this->saveAddress));

            return;
        }

        if ($session->state === 'search') {
            if ($session->expires_at?->isPast()) {
                $this->addresses->reset($customer);
                $bot->sendMessage('مهلت جستجو تمام شد. دوباره از منو جستجو را انتخاب کنید.');

                return;
            }

            if (mb_strlen($text) < 2 || mb_strlen($text) > 80) {
                $bot->sendMessage('برای جستجو بین ۲ تا ۸۰ حرف وارد کنید.');

                return;
            }

            $session->update(['state' => 'idle', 'context' => ['search' => $text], 'updated_at' => now()]);
            $this->catalog->products($bot, null, $text, 1);

            return;
        }

        $this->menu($bot);
    }

    public function onCallback(Nutgram $bot): void
    {
        $callback = $bot->callbackQuery();
        $from = $callback?->from;
        $chat = $callback?->message?->chat;

        if (! $from || $from->is_bot || ! $chat || $chat->type !== 'private') {
            return;
        }

        $customer = $this->identify->execute(
            $from->id,
            $chat->id,
            trim($from->first_name.' '.($from->last_name ?? '')),
        );

        // Acknowledge every private-chat button quickly, before the next message.
        $bot->answerCallbackQuery();
        $data = (string) ($callback->data ?? '');

        try {
            if ($data === 'menu:catalog') {
                $this->catalog->categories($bot);
            } elseif ($data === 'menu:search') {
                $this->startSearch($bot, $customer);
            } elseif ($data === 'menu:addresses') {
                $this->listAddresses($bot, $customer);
            } elseif ($data === 'menu:support') {
                $this->support($bot);
            } elseif ($data === 'menu:cart') {
                $this->cartCheckout->show($bot, $customer);
            } elseif ($data === 'menu:receipt') {
                $this->receipts->orders($bot, $customer);
            } elseif (preg_match('/^receipt:order:([1-9][0-9]*)$/', $data, $m)) {
                $this->receipts->order($bot, $customer, (int) $m[1]);
            } elseif (preg_match('/^receipt:upload:([1-9][0-9]*)$/', $data, $m)) {
                $this->receipts->start($bot, $customer, (int) $m[1]);
            } elseif ($data === 'menu:orders') {
                $bot->sendMessage('پیگیری سفارش‌ها پس از پیاده‌سازی ثبت سفارش و ارسال فعال می‌شود.');
            } elseif ($data === 'menu:home') {
                $this->menu($bot);
            } elseif ($data === 'checkout:start') {
                $this->cartCheckout->chooseAddress($bot, $customer);
            } elseif (preg_match('/^checkout:address:([1-9][0-9]*)$/', $data, $m)) {
                $this->cartCheckout->preview($bot, $customer, (int) $m[1]);
            } elseif (preg_match('/^checkout:confirm:([a-f0-9-]{36})$/', $data, $m)) {
                $this->cartCheckout->confirm($bot, $customer, $m[1]);
            } elseif (preg_match('/^cart:add:([1-9][0-9]*)$/', $data, $m)) {
                $this->carts->add($customer, (int) $m[1]);
                $this->cartCheckout->show($bot, $customer);
            } elseif (preg_match('/^cart:plus:([1-9][0-9]*)$/', $data, $m)) {
                $this->carts->add($customer, (int) $m[1]);
                $this->cartCheckout->show($bot, $customer);
            } elseif (preg_match('/^cart:(minus|remove):([1-9][0-9]*)$/', $data, $m)) {
                $line = $this->carts->cart($customer)->items()->where('product_variant_id', (int) $m[2])->first();
                if ($line) {
                    $quantity = $m[1] === 'remove' ? 0 : max(0, $line->quantity - 1);
                    $this->carts->setQuantity($customer, (int) $m[2], $quantity);
                }
                $this->cartCheckout->show($bot, $customer);
            } elseif (preg_match('/^all:([1-9][0-9]{0,3})$/', $data, $m)) {
                $this->catalog->products($bot, null, null, (int) $m[1]);
            } elseif (preg_match('/^cat:([1-9][0-9]*):([1-9][0-9]{0,3})$/', $data, $m)) {
                $this->catalog->products($bot, (int) $m[1], null, (int) $m[2]);
            } elseif (preg_match('/^item:([1-9][0-9]*)$/', $data, $m)) {
                $this->catalog->details($bot, (int) $m[1]);
            } elseif (preg_match('/^gallery:([1-9][0-9]*):([0-9]{1,3})$/', $data, $m)) {
                $this->catalog->gallery($bot, (int) $m[1], (int) $m[2]);
            } elseif (preg_match('/^variant:([1-9][0-9]*)$/', $data, $m)) {
                $this->catalog->variantImage($bot, (int) $m[1]);
            } elseif (preg_match('/^search:([1-9][0-9]{0,3})$/', $data, $m)) {
                $session = $this->addresses->session($customer);
                $term = $session->context['search'] ?? null;

                if (is_string($term) && $term !== '' && ! $session->expires_at?->isPast()) {
                    $this->catalog->products($bot, null, $term, (int) $m[1]);
                } else {
                    $this->startSearch($bot, $customer);
                }
            } elseif ($data === 'addr:new') {
                $bot->sendMessage($this->addresses->start($customer));
            } elseif (preg_match('/^addr:edit:([1-9][0-9]*)$/', $data, $m)) {
                $address = $customer->addresses()->find((int) $m[1]);
                $bot->sendMessage($address
                    ? $this->addresses->start($customer, $address->id)
                    : 'نشانی پیدا نشد.');
            } elseif (preg_match('/^addr:default:([1-9][0-9]*)$/', $data, $m)) {
                if ($customer->addresses()->whereKey((int) $m[1])->exists()) {
                    $this->saveAddress->setDefault($customer, (int) $m[1]);
                    $this->listAddresses($bot, $customer);
                } else {
                    $bot->sendMessage('نشانی پیدا نشد.');
                }
            } elseif (preg_match('/^addr:delete:([1-9][0-9]*)$/', $data, $m)) {
                if ($customer->addresses()->whereKey((int) $m[1])->exists()) {
                    $this->saveAddress->delete($customer, (int) $m[1]);
                    $this->listAddresses($bot, $customer);
                } else {
                    $bot->sendMessage('نشانی پیدا نشد.');
                }
            } elseif (preg_match('/^addr:page:([1-9][0-9]{0,3})$/', $data, $m)) {
                $this->listAddresses($bot, $customer, (int) $m[1]);
            } else {
                $bot->sendMessage('این گزینه معتبر نیست. از منوی اصلی ادامه دهید.');
            }
        } catch (ValidationException $exception) {
            $bot->sendMessage(collect($exception->errors())->flatten()->first() ?? 'لطفاً سبد یا اطلاعات سفارش را بازبینی کنید.');
        }
    }

    private function menu(Nutgram $bot): void
    {
        $keyboard = Keyboard::make()
            ->addRow(Button::make('🌰 محصولات', callback_data: 'menu:catalog'), Button::make('🔎 جستجو', callback_data: 'menu:search'))
            ->addRow(Button::make('📍 نشانی‌های من', callback_data: 'menu:addresses'))
            ->addRow(Button::make('🧾 ارسال رسید', callback_data: 'menu:receipt'))
            ->addRow(Button::make('🛒 سبد خرید', callback_data: 'menu:cart'), Button::make('📦 سفارش‌های من', callback_data: 'menu:orders'))
            ->addRow(Button::make('☎️ پشتیبانی', callback_data: 'menu:support'));

        $bot->sendMessage(text: 'به فروشگاه سوغات و خشکبار دنا خوش آمدید 🌿', reply_markup: $keyboard);
    }

    private function startSearch(Nutgram $bot, Customer $customer): void
    {
        $this->addresses->session($customer)->update([
            'state' => 'search',
            'context' => [],
            'expires_at' => now()->addMinutes(30),
            'updated_at' => now(),
        ]);

        $bot->sendMessage('نام یا بخشی از توضیحات محصول را بنویسید. /cancel برای لغو');
    }

    private function support(Nutgram $bot): void
    {
        $contact = trim((string) (StoreSetting::find(1)?->support_contact ?? ''));

        $bot->sendMessage($contact === ''
            ? 'اطلاعات تماس پشتیبانی هنوز ثبت نشده است.'
            : "برای ارتباط با پشتیبانی دنا:\n".$contact);
    }

    private function listAddresses(Nutgram $bot, Customer $customer, int $page = 1): void
    {
        $addresses = $customer->addresses()->orderByDesc('is_default')->orderBy('id')
            ->paginate(perPage: 5, page: min(1000, max(1, $page)));

        $keyboard = Keyboard::make();

        if ($addresses->isEmpty()) {
            $bot->sendMessage('نشانی ذخیره‌شده‌ای وجود ندارد.');
        } else {
            $text = '📍 نشانی‌های ذخیره‌شده (صفحه '.$addresses->currentPage().' از '.$addresses->lastPage().')';
            foreach ($addresses as $address) {
                $text .= "\n\n".($address->is_default ? '⭐ ' : '').$address->recipient_name
                    .' — '.$address->province.'، '.$address->city.'، '.$address->address_line
                    .' ('.$address->postal_code.')';
                $keyboard->addRow(
                    Button::make('ویرایش #'.$address->id, callback_data: 'addr:edit:'.$address->id),
                    Button::make('پیش‌فرض #'.$address->id, callback_data: 'addr:default:'.$address->id),
                    Button::make('حذف #'.$address->id, callback_data: 'addr:delete:'.$address->id),
                );
            }

            $pager = [];
            if ($addresses->currentPage() > 1) {
                $pager[] = Button::make('قبلی', callback_data: 'addr:page:'.($addresses->currentPage() - 1));
            }
            if ($addresses->hasMorePages()) {
                $pager[] = Button::make('بعدی', callback_data: 'addr:page:'.($addresses->currentPage() + 1));
            }
            if ($pager !== []) {
                $keyboard->addRow(...$pager);
            }
        }

        $keyboard->addRow(Button::make('افزودن نشانی', callback_data: 'addr:new'));
        $keyboard->addRow(Button::make('منوی اصلی', callback_data: 'menu:home'));

        $bot->sendMessage(
            text: isset($text) ? mb_substr($text, 0, 3900) : 'نشانی‌های من',
            reply_markup: $keyboard,
        );
    }
}
