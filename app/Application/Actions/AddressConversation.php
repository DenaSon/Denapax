<?php

namespace App\Application\Actions;

use App\Models\Customer;
use App\Models\TelegramSession;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AddressConversation
{
    private const FIELDS = [
        'recipient_name' => ['label' => 'نام گیرنده', 'rules' => 'required|string|max:220'],
        'recipient_mobile' => ['label' => 'شماره همراه گیرنده (مانند 09123456789)', 'rules' => 'required|string|regex:/^\\+?[0-9]{10,15}$/'],
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
            'state' => 'idle',
            'context' => [],
            'expires_at' => null,
            'updated_at' => now(),
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

        return ($addressId === null ? 'ثبت نشانی جدید' : 'ویرایش نشانی (لطفاً همه فیلدها را دوباره وارد کنید)')
            ."\n".self::FIELDS['recipient_name']['label'].' را بنویسید. /cancel برای لغو';
    }

    public function isCollecting(TelegramSession $session): bool
    {
        return str_starts_with($session->state, 'address:');
    }

    public function accept(Customer $customer, string $input, SaveCustomerAddress $action): string
    {
        $session = $this->session($customer);

        if ($session->expires_at?->isPast()) {
            $this->reset($customer);

            return 'مهلت ثبت نشانی به پایان رسید. از منوی نشانی‌ها دوباره شروع کنید.';
        }

        $field = substr($session->state, strlen('address:'));
        $spec = self::FIELDS[$field] ?? null;
        if ($spec === null) {
            $this->reset($customer);

            return 'فرآیند ثبت نشانی بسته شد.';
        }

        $value = trim($input);
        $rules = [$field => $spec['rules']];
        $validation = Validator::make([$field => $value], $rules);
        if ($validation->fails()) {
            return 'مقدار «'.$spec['label'].'» معتبر نیست. دوباره وارد کنید.';
        }

        $context = $session->context ?? [];
        $context['fields'][$field] = $value;
        $keys = array_keys(self::FIELDS);
        $position = array_search($field, $keys, true);
        $next = $keys[$position + 1] ?? null;

        if ($next !== null) {
            $session->update([
                'state' => 'address:'.$next,
                'context' => $context,
                'expires_at' => now()->addMinutes(30),
                'updated_at' => now(),
            ]);

            return 'لطفاً «'.self::FIELDS[$next]['label'].'» را وارد کنید. /cancel برای لغو';
        }

        try {
            $address = $action->execute($customer, $context['fields'], $context['editing_id'] ?? null);
        } catch (ValidationException) {
            $this->reset($customer);

            return 'اطلاعات نشانی معتبر نبود. لطفاً دوباره از منوی نشانی‌ها شروع کنید.';
        }

        if ($customer->full_name === null && $customer->mobile === null) {
            $customer->update([
                'full_name' => $address->recipient_name,
                'mobile' => $address->recipient_mobile,
            ]);
        }

        $this->reset($customer);

        return '✅ نشانی ذخیره شد. می‌توانید از فهرست نشانی‌ها آن را برای سفارش انتخاب کنید.';
    }
}
