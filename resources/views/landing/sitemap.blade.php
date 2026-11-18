<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
    </url>
    @foreach ($categories as $category)
        <url>
            <loc>{{ route('categories.show', ['slug' => $category->slug]) }}</loc>
        </url>
    @endforeach
    @foreach ($products as $product)
        <url>
            <loc>{{ route('products.show', ['slug' => $product->slug]) }}</loc>
        </url>
    @endforeach
</urlset>
