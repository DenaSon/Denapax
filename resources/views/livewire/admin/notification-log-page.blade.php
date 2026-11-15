<div class="space-y-5">
    <div>
        <p class="admin-eyebrow">گزارش سیستم</p>
        <h1 class="admin-page-title">اعلان‌های سفارش در وب</h1>
        <p class="admin-description">اعلان‌های مربوط به سفارش و پرداخت در حساب مشتری ثبت می‌شوند. این گزارش به معنای ارسال پیامک نیست و به تلگرام ارتباطی ندارد.</p>
    </div>
    <section class="admin-surface" aria-label="فیلتر ثبت اعلان‌ها">
        <label class="form-control max-w-xs">
            <span class="label font-bold">وضعیت ثبت اعلان</span>
            <select wire:model.live="status" class="select select-bordered w-full">
                <option value="failed">ناموفق</option>
                <option value="queued">در صف</option>
                <option value="sending">در حال ثبت</option>
                <option value="sent">ثبت‌شده</option>
                <option value="all">همه</option>
            </select>
        </label>
    </section>
    <section class="admin-surface space-y-4" aria-label="فهرست اعلان‌ها">
        <div class="admin-table-wrap">
            <table class="table w-full">
                <thead><tr><th>رویداد</th><th>شناسه مشتری</th><th>وضعیت</th><th>تلاش‌ها</th><th>نوع خطا</th><th>آخرین تغییر</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr wire:key="notification-{{ $log->id }}">
                        <td class="admin-copy font-semibold">{{ $log->event_type }}</td>
                        <td>{{ $log->customer_id }}</td>
                        <td><span class="admin-state admin-state-{{ \App\Support\AdminLabels::tone($log->status) }}">{{ \App\Support\AdminLabels::notification($log->status) }}</span></td>
                        <td>{{ $log->attempts }}</td>
                        <td class="admin-copy text-xs">{{ $log->last_error ?? '—' }}</td>
                        <td class="whitespace-nowrap">{{ $log->updated_at?->format('Y/m/d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center">اعلانی در این وضعیت پیدا نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </section>
</div>
