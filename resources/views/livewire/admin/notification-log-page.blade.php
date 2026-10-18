<div class="space-y-5">
    <h1 class="text-2xl font-bold">گزارش ارسال اعلان‌های تلگرام</h1>
    <x-card>
        <p class="mb-3 text-sm opacity-70">اعلان‌ها با Queue ارسال می‌شوند. در صورت قطع تلگرام، ارسال تا پنج بار مجدداً امتحان می‌شود؛ وضعیت خطاها بدون نمایش توکن یا نشانی مشتری ثبت شده است.</p>
        <label class="form-control">
            <span class="label">وضعیت ارسال</span>
            <select wire:model.live="status" class="select max-w-xs">
                <option value="failed">ناموفق</option>
                <option value="queued">در صف</option>
                <option value="sending">در حال ارسال</option>
                <option value="sent">ارسال‌شده</option>
                <option value="all">همه</option>
            </select>
        </label>
    </x-card>
    <x-card>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>رویداد</th><th>شناسه مشتری</th><th>وضعیت</th><th>تلاش‌ها</th><th>نوع خطا</th><th>آخرین تغییر</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr wire:key="notification-{{ $log->id }}">
                        <td>{{ $log->event_type }}</td>
                        <td>{{ $log->customer_id }}</td>
                        <td>{{ $log->status }}</td>
                        <td>{{ $log->attempts }} / 5</td>
                        <td>{{ $log->last_error ?? '—' }}</td>
                        <td>{{ $log->updated_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">اعلانی با این وضعیت پیدا نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $logs->links() }}</div>
    </x-card>
</div>
