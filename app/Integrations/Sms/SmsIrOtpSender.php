<?php

namespace App\Integrations\Sms;

use App\Support\CustomerOtpPolicy;
use Ipe\Sdk\SmsIrService;
use RuntimeException;
use Throwable;

class SmsIrOtpSender
{
    public function __construct(private readonly SmsIrService $smsIr) {}

    public function send(string $mobile, string $code): void
    {
        $apiKey = trim((string) config('services.smsir.api_key', ''));
        $templateId = filter_var(config('services.smsir.verify_template_id'), FILTER_VALIDATE_INT);
        $parameterName = trim((string) config('services.smsir.verify_parameter', 'HOURS'));

        if ($apiKey === ''
            || $templateId === false
            || $templateId < 1
            || ! preg_match('/^[A-Za-z][A-Za-z0-9_]{0,49}$/D', $parameterName)
            || ! preg_match('/^[0-9]{'.CustomerOtpPolicy::length().'}$/D', $code)) {
            throw new RuntimeException('SMS.ir OTP is not configured correctly.');
        }

        try {
            $result = $this->smsIr->verifySend($mobile, $templateId, [
                [
                    'name' => $parameterName,
                    'value' => $code,
                ],
            ]);
        } catch (Throwable) {
            // SDK/provider exceptions can contain response details. Never forward
            // them to logs or user-facing errors where secrets could be exposed.
            throw new RuntimeException('SMS.ir OTP delivery failed.');
        }

        $status = is_object($result) ? ($result->status ?? null) : null;
        $data = is_object($result) ? ($result->data ?? null) : null;
        $messageId = is_array($data) ? ($data['messageId'] ?? null) : null;
        $validMessageId = is_int($messageId)
            || (is_string($messageId) && ctype_digit($messageId));

        if (! in_array($status, [1, '1'], true)
            || ! $validMessageId
            || (int) $messageId < 1) {
            throw new RuntimeException('SMS.ir OTP delivery failed.');
        }
    }
}
