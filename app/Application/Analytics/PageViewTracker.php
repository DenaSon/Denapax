<?php

namespace App\Application\Analytics;

use Illuminate\Support\Facades\DB;

final class PageViewTracker
{
    private const DUPLICATE_WINDOW_SECONDS = 10;

    /**
     * Count a real displayed-page event, not a request to render/prefetch HTML.
     * The browser token is never persisted; the database stores only a
     * day-scoped keyed hash. MySQL unique constraints arbitrate concurrent beacons.
     */
    public function track(string $pageType, int $productId, string $visitorId): bool
    {
        $now = now();
        $date = $now->copy()->timezone('Asia/Tehran')->toDateString();
        $visitorHash = hash_hmac('sha256', $date.':'.$visitorId, (string) config('app.key'));

        return DB::transaction(function () use ($pageType, $productId, $date, $visitorHash, $now): bool {
            $identity = [
                'visit_date' => $date,
                'page_type' => $pageType,
                'product_id' => $productId,
                'visitor_hash' => $visitorHash,
            ];

            $isNew = DB::table('page_view_visitors')->insertOrIgnore($identity + [
                'last_viewed_at' => $now,
            ]) === 1;

            if (! $isNew) {
                // Atomic conditional update stops duplicate initial navigated/DOMContentLoaded
                // events and double-posts across multiple application replicas.
                $accepted = DB::table('page_view_visitors')
                    ->where($identity)
                    ->where('last_viewed_at', '<=', $now->copy()->subSeconds(self::DUPLICATE_WINDOW_SECONDS))
                    ->update(['last_viewed_at' => $now]) === 1;

                if (! $accepted) {
                    return false;
                }
            }

            $key = [
                'view_date' => $date,
                'page_type' => $pageType,
                'product_id' => $productId,
            ];

            DB::table('page_view_daily')->insertOrIgnore($key + [
                'views' => 0,
                'unique_visitors' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('page_view_daily')->where($key)->incrementEach([
                'views' => 1,
                'unique_visitors' => $isNew ? 1 : 0,
            ], ['updated_at' => $now]);

            return true;
        }, 3);
    }
}
