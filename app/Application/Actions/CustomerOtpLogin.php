<?php

namespace App\Application\Actions;

use App\Models\Customer;
use App\Models\CustomerLoginCode;
use App\Support\CustomerOtpSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class CustomerOtpLogin
{
    public function __construct(private readonly CustomerOtpSender $sender) {}

    public function request(string $mobile, string $ip): void
    {
        $mobileKey = 'customer:otp:send:'.hash('sha256', $mobile);
        $ipKey = 'customer:otp:ip:'.hash('sha256', $ip);

        if (RateLimiter::tooManyAttempts($mobileKey, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            throw ValidationException::withMessages(['mobile' => 'تعداد درخواست‌ها زیاد است. کمی بعد دوباره تلاش کنید.']);
        }

        $existing = CustomerLoginCode::query()->find($mobile);
        if ($existing && $existing->last_sent_at->gt(now()->subSeconds(60))) {
            throw ValidationException::withMessages(['mobile' => 'برای دریافت مجدد کد، یک دقیقه صبر کنید.']);
        }

        $code = (string) random_int(100000, 999999);

        // Persist the hash before delivery; no successful login is possible
        // when the transport fails, and no plaintext OTP enters the database.
        CustomerLoginCode::query()->updateOrCreate(['mobile' => $mobile], [
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
            'last_sent_at' => now(),
        ]);

        RateLimiter::hit($mobileKey, 3600);
        RateLimiter::hit($ipKey, 3600);

        try {
            $this->sender->send($mobile, $code);
        } catch (\Throwable $exception) {
            CustomerLoginCode::query()->whereKey($mobile)->delete();
            report(new \RuntimeException('Customer OTP delivery failed.'));

            throw ValidationException::withMessages(['mobile' => 'ارسال کد در حال حاضر ممکن نیست. دوباره تلاش کنید.']);
        }
    }

    public function verify(string $mobile, string $code): Customer
    {
        $customer = DB::transaction(function () use ($mobile, $code): ?Customer {
            $challenge = CustomerLoginCode::query()->whereKey($mobile)->lockForUpdate()->first();

            if (! $challenge || $challenge->expires_at->isPast() || $challenge->attempts >= 5) {
                throw ValidationException::withMessages(['code' => 'کد نامعتبر یا منقضی شده است.']);
            }

            if (! Hash::check($code, $challenge->code_hash)) {
                $challenge->increment('attempts');

                return null;
            }

            $challenge->delete();

            $customer = Customer::query()->firstOrCreate(
                ['mobile' => $mobile],
                ['mobile_verified_at' => now()],
            );

            if (! $customer->mobile_verified_at) {
                $customer->forceFill(['mobile_verified_at' => now()])->save();
            }

            return $customer;
        }, 3);

        if (! $customer) {
            throw ValidationException::withMessages(['code' => 'کد نامعتبر یا منقضی شده است.']);
        }

        return $customer;
    }
}
