<?php

namespace App\Application\Actions;

use App\Models\Customer;
use App\Models\TelegramSession;
use App\Support\TelegramHtml;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AddressConversation
{
    private const FIELDS = [
        'recipient_name' => ['label' => 'نام و نام خانوادگی گیرنده', 'rules' => 'required|string|max:220'],
        'recipient_mobile' => ['label' => 'شماره همراه گیرنده (مانند 09123456789)', 'rules' => 'required|string|regex:/^\+?[0-9]{10,15}$/'],
        'province' => ['label' => 'استان', 'rules' => 'required|string|max:100'],
        'city' => ['label' => 'شهر', 'rules' => 'required|string|max:100'],
        'address_line' => ['label' => 'نشانی کامل (حداقل ۸ حرف)', 'rules' => 'required|string|min:8|max:2000'],
        'postal_code' => ['label' => 'کد پستی ده‌رقمی', 'rules' => 'required|string|regex:/^[0-9]{10}$/'],
    ];

    public function session(Customer $customer): TelegramSession
    {
        return TelegramSession::query()->firstOrCreate(
            ['customer_id' => $customer->id],
            ['state' => 'idle', 'context' => []],
        );
    }

    public function reset(Customer $customer): void
    {
        $this->session($customer)->update([
            'state' => 'idle', 'context' => [], 'expires_at' => null, 'updated_at' => now(),
        ]);
    }

    public function start(Customer $customer, ?int $addressId = null): string
    {
        if ($addressId !== null) {
            $customer->addresses()->findOrFail($addressId);
        }

        $this->session($customer)->update([
            'state' => 'address:recipient_name',
            'context' => ['editing_id' => $addressId, 'fields' => []],
            'expires_at' => now()->addMinutes(30),
            'updated_at' => now(),
        ]);

        return $this->prompt('recipient_name');
    }

    public function isCollecting(TelegramSession $session): bool
    {
        return str_starts_with($session->state, 'address:') && $session->state !== 'address:review';
    }

    public function isReviewing(TelegramSession $session): bool
    {
        return $session->state === 'address:review' && $session->expires_at?->isFuture();
    }

    private function prompt(string $field): string
    {
        return '✍️ '.TelegramHtml::bold(self::FIELDS[$field]['label'])
            ."\nاطلاعات خواسته‌شده را وارد کنید یا از دکمه «❌ لغو» استفاده کنید.";
    }

    public function accept(Customer $customer, string $input, SaveCustomerAddress $action): string
    {
        $session = $this->session($customer);
        if ($session->expires_at?->isPast()) {
            $this->reset($customer);

            return 'مهلت ثبت نشانی تمام شد. دوباره از منو شروع کنید.';
        }

        $field = substr($session->state, strlen('address:'));
        if (! isset(self::FIELDS[$field])) {
            return 'از دکمه‌های فرم استفاده کنید.';
        }

        $value = trim($input);
        $validation = Validator::make([$field => $value], [$field => self::FIELDS[$field]['rules']]);
        if ($validation->fails()) {
            return 'مقدار «'.TelegramHtml::escape(self::FIELDS[$field]['label']).'» معتبر نیست. دوباره وارد کنید.';
        }

        $context = $session->context ?? [];
        $context['fields'][$field] = $value;
        $keys = array_keys(self::FIELDS);
        $position = array_search($field, $keys, true);
        $next = ($context['correction'] ?? false) ? null : ($keys[$position + 1] ?? null);

        $session->update([
            'state' => $next === null ? 'address:review' : 'address:'.$next,
            'context' => array_diff_key($context, ['correction' => true]),
            'expires_at' => now()->addMinutes(30),
            'updated_at' => now(),
        ]);

        return $next === null ? $this->review($customer) : $this->prompt($next);
    }

    public function review(Customer $customer): string
    {
        $session = $this->session($customer);
        if (! $this->isReviewing($session)) {
            return 'فرم نشانی منقضی شده است؛ دوباره شروع کنید.';
        }

        $fields = $session->context['fields'] ?? [];
        if (count(array_intersect(array_keys(self::FIELDS), array_keys($fields))) !== count(self::FIELDS)) {
            return 'اطلاعات نشانی کامل نیست؛ دوباره شروع کنید.';
        }

        $text = '📍 '.TelegramHtml::bold('بررسی اطلاعات تحویل');
        foreach (self::FIELDS as $key => $spec) {
            $text .= "\n".TelegramHtml::bold($spec['label'].': ').TelegramHtml::escape($fields[$key]);
        }

        return $text."\n\nلطفاً اطلاعات را تأیید یا اصلاح کنید.";
    }

    public function correction(Customer $customer, string $field): string
    {
        $session = $this->session($customer);
        if (! $this->isReviewing($session) || ! isset(self::FIELDS[$field])) {
            return 'فرم منقضی شده است. دوباره شروع کنید.';
        }

        $context = $session->context;
        $context['correction'] = true;
        $session->update([
            'state' => 'address:'.$field,
            'context' => $context,
            'expires_at' => now()->addMinutes(30),
        ]);

        return $this->prompt($field);
    }

    public function confirm(Customer $customer, SaveCustomerAddress $action): string
    {
        return DB::transaction(function () use ($customer, $action): string {
            $session = TelegramSession::query()->where('customer_id', $customer->id)->lockForUpdate()->first();
            if (! $session || ! $this->isReviewing($session)) {
                return 'این فرم قبلاً ذخیره یا منقضی شده است.';
            }

            $context = $session->context ?? [];
            try {
                $address = $action->execute($customer, $context['fields'] ?? [], $context['editing_id'] ?? null);
            } catch (ValidationException) {
                return 'نشانی معتبر نیست. اطلاعات را اصلاح کنید.';
            }

            if ($customer->full_name === null && $customer->mobile === null) {
                $customer->update([
                    'full_name' => $address->recipient_name,
                    'mobile' => $address->recipient_mobile,
                ]);
            }
            $this->reset($customer);

            return '✅ نشانی ذخیره شد. از فهرست نشانی‌ها می‌توانید آن را انتخاب کنید.';
        }, 3);
    }

    public function correctionFields(): array
    {
        return collect(self::FIELDS)->mapWithKeys(
            fn (array $spec, string $key): array => [$key => $spec['label']],
        )->all();
    }
}
