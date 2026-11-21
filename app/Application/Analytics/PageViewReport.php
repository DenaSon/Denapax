<?php

namespace App\Application\Analytics;

use Illuminate\Support\Facades\DB;

final class PageViewReport
{
    /**
     * Compact all-time counters plus today/yesterday in Tehran time.
     * Aggregates use the daily table only; no per-visitor rows are read.
     */
    public function overview(): array
    {
        $today = now('Asia/Tehran')->toDateString();
        $yesterday = now('Asia/Tehran')->subDay()->toDateString();

        $totals = DB::table('page_view_daily')
            ->selectRaw('COALESCE(SUM(views), 0) AS total_views')
            ->selectRaw('COALESCE(SUM(CASE WHEN view_date = ? THEN views ELSE 0 END), 0) AS today_views', [$today])
            ->selectRaw('COALESCE(SUM(CASE WHEN view_date = ? THEN views ELSE 0 END), 0) AS yesterday_views', [$yesterday])
            ->selectRaw("COALESCE(SUM(CASE WHEN page_type = 'home' THEN views ELSE 0 END), 0) AS home_views")
            ->selectRaw("COALESCE(SUM(CASE WHEN page_type = 'product' THEN views ELSE 0 END), 0) AS product_views")
            ->first();

        $topProducts = DB::table('page_view_daily as daily')
            ->join('products as products', 'products.id', '=', 'daily.product_id')
            ->where('daily.page_type', 'product')
            ->select('products.id', 'products.title')
            ->selectRaw('SUM(daily.views) AS view_count')
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('view_count')
            ->orderBy('products.id')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'views' => (int) $row->view_count,
            ])
            ->all();

        return [
            'today' => (int) $totals->today_views,
            'yesterday' => (int) $totals->yesterday_views,
            'total' => (int) $totals->total_views,
            'home' => (int) $totals->home_views,
            'products' => (int) $totals->product_views,
            'topProducts' => $topProducts,
        ];
    }
}
