<?php

namespace App\Http\Controllers;

use App\Models\TelegramUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use Throwable;

class TelegramWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.telegram.webhook_secret');
        if ($secret === '') {
            return response()->json(['error' => 'Webhook not configured'], 503);
        }

        if (! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token', ''))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'update_id' => 'required|integer|min:1',
        ]);

        $updateId = (int) $data['update_id'];
        $claimed = DB::transaction(function () use ($updateId): bool {
            $inserted = TelegramUpdate::query()->insertOrIgnore([
                'update_id' => $updateId,
                'status' => 'processing',
                'received_at' => now(),
                'started_at' => now(),
            ]);

            $update = TelegramUpdate::query()->lockForUpdate()->findOrFail($updateId);

            if ($inserted === 0 && $update->status === 'done') {
                return false;
            }

            // A concurrent invocation or recently started interrupted process.
            if ($inserted === 0 && $update->status === 'processing' && $update->started_at?->gt(now()->subMinutes(2))) {
                return false;
            }

            $update->update([
                'status' => 'processing',
                'started_at' => now(),
                'processed_at' => null,
                'error_summary' => null,
            ]);

            return true;
        });

        if (! $claimed) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        try {
            app(Nutgram::class)->run();
            TelegramUpdate::query()->whereKey($updateId)->update([
                'status' => 'done',
                'processed_at' => now(),
                'error_summary' => null,
            ]);
        } catch (Throwable $e) {
            TelegramUpdate::query()->whereKey($updateId)->update([
                'status' => 'failed',
                'error_summary' => mb_substr($e::class, 0, 240),
            ]);

            // Provider exceptions may contain bot-token URLs or customer data.
            // Do not send the exception object/message to application logs.
            Log::warning('Telegram webhook processing failed', [
                'exception_type' => $e::class,
                'update_id' => $updateId,
            ]);

            return response()->json(['error' => 'Temporary failure'], 503);
        }

        return response()->json(['ok' => true]);
    }
}
