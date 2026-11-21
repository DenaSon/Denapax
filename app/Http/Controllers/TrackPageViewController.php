<?php

namespace App\Http\Controllers;

use App\Application\Analytics\PageViewTracker;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

final class TrackPageViewController extends Controller
{
    public function __invoke(Request $request, PageViewTracker $tracker): Response
    {
        // Never count internal/admin browsing or recognizable automated clients.
        if ($request->user('web') !== null || preg_match(
            '/bot|crawl|spider|slurp|headless|lighthouse|pagespeed|preview|facebookexternalhit|curl|wget|python-requests/i',
            (string) $request->userAgent()
        )) {
            return response()->noContent();
        }

        $input = $request->validate([
            'page_type' => ['required', Rule::in(['home', 'product'])],
            'product_id' => ['required', 'integer', 'min:0'],
            'visitor_id' => ['required', 'string', 'regex:/^[a-f0-9]{32}$/D'],
        ]);

        $productId = (int) $input['product_id'];

        if ($input['page_type'] === 'home' && $productId !== 0) {
            abort(422);
        }

        if ($input['page_type'] === 'product' && (
            $productId === 0
            || ! Product::query()->publiclyOrderable()->whereKey($productId)->exists()
        )) {
            return response()->noContent();
        }

        $tracker->track($input['page_type'], $productId, $input['visitor_id']);

        return response()->noContent();
    }
}
