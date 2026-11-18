<?php

namespace App\Support;

/**
 * Factual site identity and breadcrumb markup shared by public SEO pages.
 */
final class StoreSeo
{
    public static function organization(): array
    {
        return [
            '@type' => 'OnlineStore',
            '@id' => route('home').'#organization',
            'name' => 'دناپکس',
            'alternateName' => 'DenaPax',
            'url' => route('home'),
            'description' => 'فروشگاه اینترنتی سوغات، خشکبار و محصولات محلی منطقه دنا و سی‌سخت',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => asset('images/denapax-mark.svg'),
                'width' => 512,
                'height' => 512,
            ],
        ];
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => route('home').'#website',
            'name' => 'دناپکس',
            'alternateName' => 'DenaPax',
            'url' => route('home'),
            'inLanguage' => 'fa-IR',
            'publisher' => ['@id' => route('home').'#organization'],
        ];
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                static fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ],
                $items,
                array_keys($items),
            ),
        ];
    }
}
