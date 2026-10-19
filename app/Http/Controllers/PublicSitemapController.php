<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;

class PublicSitemapController extends Controller
{
    public function __invoke(): Response
    {
        // Streaming model cursor: do not load every product or any private fields.
        $products = Product::query()
            ->publiclyOrderable()
            ->select(['id', 'slug'])
            ->orderBy('id')
            ->cursor();

        return response()
            ->view('landing.sitemap', compact('products'))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300');
    }
}
