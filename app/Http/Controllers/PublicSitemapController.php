<?php

namespace App\Http\Controllers;

use App\Models\Category;
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

        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($query) => $query->publiclyOrderable())
            ->select(['slug'])
            ->orderBy('id')
            ->cursor();

        return response()
            ->view('landing.sitemap', compact('products', 'categories'))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300');
    }
}
