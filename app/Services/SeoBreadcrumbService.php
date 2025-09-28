<?php

namespace App\Services;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;

class SeoBreadcrumbService
{
    /**
     * Generate breadcrumb structured data for game pages
     */
    public function generateBreadcrumbData(array $breadcrumbs): array
    {
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [],
        ];

        foreach ($breadcrumbs as $index => $breadcrumb) {
            $structuredData['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['name'],
                'item' => $breadcrumb['url'] ?? null,
            ];
        }

        return $structuredData;
    }

    /**
     * Generate breadcrumbs for game dashboard
     */
    public function getDashboardBreadcrumbs(Player $player): array
    {
        return [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Game', 'url' => url('/game')],
            ['name' => $player->name."'s Dashboard", 'url' => url('/game/dashboard')],
        ];
    }

    /**
     * Generate breadcrumbs for village page
     */
    public function getVillageBreadcrumbs(Village $village, Player $player): array
    {
        return [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Game', 'url' => url('/game')],
            ['name' => $player->name."'s Dashboard", 'url' => url('/game/dashboard')],
            ['name' => $village->name, 'url' => url('/game/village/'.$village->id)],
        ];
    }

    /**
     * Generate breadcrumbs for world map
     */
    public function getWorldMapBreadcrumbs(World $world): array
    {
        return [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Game', 'url' => url('/game')],
            ['name' => 'World Map - '.$world->name, 'url' => url('/game/map')],
        ];
    }

    /**
     * Generate breadcrumbs for game features
     */
    public function getGameFeaturesBreadcrumbs(): array
    {
        return [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Game', 'url' => url('/game')],
            ['name' => 'Features', 'url' => url('/game/features')],
        ];
    }

    /**
     * Add breadcrumb structured data to SEO
     */
    public function addBreadcrumbToSeo(array $breadcrumbs): void
    {
        $structuredData = $this->generateBreadcrumbData($breadcrumbs);
        seo()->metaTag('script[type="application/ld+json"]', json_encode($structuredData));
    }
}
