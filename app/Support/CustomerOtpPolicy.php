<?php

namespace App\Support;

use RuntimeException;

/**
 * Central OTP policy. Invalid deployment configuration fails closed.
 */
final class CustomerOtpPolicy
{
    public static function length(): int
    {
        $length = (int) config('customer_auth.otp_length', 4);

        if (! in_array($length, [4, 6], true)) {
            throw new RuntimeException('Customer OTP length must be 4 or 6.');
        }

        return $length;
    }

    public static function ttlSeconds(): int
    {
        $seconds = (int) config('customer_auth.otp_ttl_seconds', 120);

        if ($seconds < 60 || $seconds > 600) {
            throw new RuntimeException('Customer OTP lifetime is outside the allowed range.');
        }

        return $seconds;
    }

    public static function maxAttempts(): int
    {
        $attempts = (int) config('customer_auth.otp_max_attempts', 3);

        if ($attempts < 1 || $attempts > 5) {
            throw new RuntimeException('Customer OTP attempt limit is outside the allowed range.');
        }

        return $attempts;
    }
}
