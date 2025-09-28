<?php

namespace App\Helpers;

use App\Services\GameSeoService;

class SeoHelper
{
    protected static GameSeoService $seoService;

    /**
     * Get the SEO service instance
     */
    protected static function getSeoService(): GameSeoService
    {
        if (! isset(self::$seoService)) {
            self::$seoService = app(GameSeoService::class);
        }

        return self::$seoService;
    }

    /**
     * Set page title with site name
     */
    public static function title(string $title, ?string $siteName = null): void
    {
        $siteName = $siteName ?: config('seo.site_name', 'Travian Game');
        seo()->title($title, $siteName);
    }

    /**
     * Set page description
     */
    public static function description(string $description): void
    {
        seo()->description($description);
    }

    /**
     * Set page keywords
     */
    public static function keywords(string|array $keywords): void
    {
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }

        seo()->keywords($keywords);
    }

    /**
     * Set page images
     */
    public static function images(array $images): void
    {
        seo()->images($images);
    }

    /**
     * Set canonical URL
     */
    public static function canonical(?string $url = null): void
    {
        self::getSeoService()->setCanonicalUrl($url);
    }

    /**
     * Set robots meta tag
     */
    public static function robots(array $robots = []): void
    {
        self::getSeoService()->setRobotsMeta($robots);
    }

    /**
     * Set Twitter Card metadata
     */
    public static function twitter(array $data): void
    {
        $seo = seo();

        if (isset($data['enabled'])) {
            $seo->twitterEnabled($data['enabled']);
        }

        if (isset($data['site'])) {
            $seo->twitterSite($data['site']);
        }

        if (isset($data['creator'])) {
            $seo->twitterCreator($data['creator']);
        }

        if (isset($data['title'])) {
            $seo->twitterTitle($data['title']);
        }

        if (isset($data['description'])) {
            $seo->twitterDescription($data['description']);
        }

        if (isset($data['image'])) {
            $seo->twitterImage($data['image']);
        }
    }

    /**
     * Set Open Graph metadata
     */
    public static function openGraph(array $data): void
    {
        $seo = seo();

        if (isset($data['title'])) {
            $seo->ogTitle($data['title']);
        }

        if (isset($data['description'])) {
            $seo->ogDescription($data['description']);
        }

        if (isset($data['image'])) {
            $seo->ogImage($data['image']);
        }

        if (isset($data['type'])) {
            $seo->ogType($data['type']);
        }

        if (isset($data['url'])) {
            $seo->ogUrl($data['url']);
        }
    }

    /**
     * Generate SEO-friendly slug
     */
    public static function slug(string $text): string
    {
        return \Str::slug($text);
    }

    /**
     * Truncate text for SEO descriptions
     */
    public static function truncateDescription(string $text, int $length = 160): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - 3).'...';
    }

    /**
     * Generate breadcrumb structured data
     */
    public static function breadcrumbs(array $items): void
    {
        $breadcrumbData = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [],
        ];

        foreach ($items as $index => $item) {
            $breadcrumbData['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'] ?? null,
            ];
        }

        seo()->addMeta('application/ld+json', json_encode($breadcrumbData), 'script');
    }

    /**
     * Generate FAQ structured data
     */
    public static function faq(array $questions): void
    {
        $faqData = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [],
        ];

        foreach ($questions as $qa) {
            $faqData['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $qa['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $qa['answer'],
                ],
            ];
        }

        seo()->addMeta('application/ld+json', json_encode($faqData), 'script');
    }

    /**
     * Set game-specific SEO metadata
     */
    public static function gamePage(string $page, array $data = []): void
    {
        $seoService = self::getSeoService();

        switch ($page) {
            case 'index':
                $seoService->setGameIndexSeo();
                $seoService->setGameStructuredData();

                break;

            case 'dashboard':
                if (isset($data['player'])) {
                    $seoService->setDashboardSeo($data['player']);
                }

                break;

            case 'village':
                if (isset($data['village']) && isset($data['player'])) {
                    $seoService->setVillageSeo($data['village'], $data['player']);
                }

                break;

            case 'map':
                if (isset($data['world'])) {
                    $seoService->setWorldMapSeo($data['world']);
                }

                break;

            case 'features':
                $seoService->setGameFeaturesSeo();

                break;
        }
    }

    /**
     * Validate SEO metadata
     */
    public static function validate(array $metadata): array
    {
        $errors = [];

        // Check required fields
        if (empty($metadata['title'])) {
            $errors[] = 'Title is required';
        } elseif (strlen($metadata['title']) > 60) {
            $errors[] = 'Title should be 60 characters or less';
        }

        if (empty($metadata['description'])) {
            $errors[] = 'Description is required';
        } elseif (strlen($metadata['description']) > 160) {
            $errors[] = 'Description should be 160 characters or less';
        }

        // Check image dimensions if provided
        if (isset($metadata['image'])) {
            $imagePath = public_path(str_replace(asset(''), '', $metadata['image']));
            if (file_exists($imagePath)) {
                $imageSize = getimagesize($imagePath);
                if ($imageSize && ($imageSize[0] < 1200 || $imageSize[1] < 630)) {
                    $errors[] = 'Image should be at least 1200x630 pixels for optimal social sharing';
                }
            }
        }

        return $errors;
    }
}
