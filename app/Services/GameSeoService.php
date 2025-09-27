<?php

namespace App\Services;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;

class GameSeoService
{
    /**
     * Set SEO metadata for the main game index page
     */
    public function setGameIndexSeo(): void
    {
        $config = config('seo');
        
        seo()
            ->title($config['default_title'], $config['site_name'])
            ->description($config['default_description'])
            ->keywords($config['default_keywords'])
            ->images($this->getOptimizedImages([
                $config['default_image'],
                'img/travian/village-preview.jpg',
                'img/travian/world-map.jpg'
            ]))
            ->twitterEnabled($config['twitter']['enabled'])
            ->twitterSite($config['twitter']['site'])
            ->twitterCreator($config['twitter']['creator'])
            ->twitterTitle($config['default_title'])
            ->twitterDescription($config['default_description'])
            ->twitterImage(asset($config['default_image']));
    }

    /**
     * Set SEO metadata for the game dashboard
     */
    public function setDashboardSeo(Player $player): void
    {
        $villageCount = $player->villages->count();
        $totalPopulation = $player->villages->sum('population');
        
        seo()
            ->title("Dashboard - {$player->name}", 'Travian Game')
            ->description("Manage your {$villageCount} village(s) and {$totalPopulation} population in Travian. Build, expand, and strategize your way to victory in the ancient world.")
            ->images([
                asset('img/travian/dashboard-preview.jpg'),
                asset('img/travian/village-overview.jpg')
            ])
            ->twitterEnabled(true)
            ->twitterTitle("Dashboard - {$player->name}")
            ->twitterDescription("Manage {$villageCount} village(s) and {$totalPopulation} population in Travian.")
            ->twitterImage(asset('img/travian/dashboard-preview.jpg'));
    }

    /**
     * Set SEO metadata for a specific village
     */
    public function setVillageSeo(Village $village, Player $player): void
    {
        $villageName = $village->name ?: "Village at ({$village->x}|{$village->y})";
        
        seo()
            ->title("{$villageName} - {$player->name}", 'Travian Game')
            ->description("Manage {$villageName} with {$village->population} population in Travian. Build structures, manage resources, and expand your empire in the ancient world.")
            ->images([
                asset('img/travian/village-preview.jpg'),
                asset('img/travian/building-grid.jpg')
            ])
            ->twitterEnabled(true)
            ->twitterTitle("{$villageName} - {$player->name}")
            ->twitterDescription("Manage {$villageName} with {$village->population} population in Travian.")
            ->twitterImage(asset('img/travian/village-preview.jpg'));
    }

    /**
     * Set SEO metadata for the world map
     */
    public function setWorldMapSeo(World $world = null): void
    {
        $worldName = $world ? $world->name : 'Ancient World';
        
        seo()
            ->title("World Map - {$worldName}", 'Travian Game')
            ->description("Explore the {$worldName} in Travian. Discover villages, plan attacks, and expand your empire across the ancient world map.")
            ->images([
                asset('img/travian/world-map.jpg'),
                asset('img/travian/map-overview.jpg')
            ])
            ->twitterEnabled(true)
            ->twitterTitle("World Map - {$worldName}")
            ->twitterDescription("Explore the {$worldName} in Travian. Discover villages and plan your strategy.")
            ->twitterImage(asset('img/travian/world-map.jpg'));
    }

    /**
     * Set SEO metadata for game features/about page
     */
    public function setGameFeaturesSeo(): void
    {
        seo()
            ->title('Game Features - Travian Online Game', 'Travian Game')
            ->description('Discover the amazing features of Travian Online Game: village building, resource management, military strategy, alliances, and epic battles in the ancient world.')
            ->images([
                asset('img/travian/features-preview.jpg'),
                asset('img/travian/battle-system.jpg'),
                asset('img/travian/alliance-system.jpg')
            ])
            ->twitterEnabled(true)
            ->twitterTitle('Game Features - Travian Online Game')
            ->twitterDescription('Discover amazing features: village building, resource management, military strategy, and epic battles.')
            ->twitterImage(asset('img/travian/features-preview.jpg'));
    }

    /**
     * Set JSON-LD structured data for game content
     */
    public function setGameStructuredData(): void
    {
        $config = config('seo');
        $jsonLdConfig = $config['json_ld'];
        
        if (!$jsonLdConfig['enabled']) {
            return;
        }

        // Game structured data
        $gameData = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoGame',
            'name' => $config['default_title'],
            'description' => $config['default_description'],
            'genre' => ['Strategy', 'MMO', 'Browser Game'],
            'gamePlatform' => 'Web Browser',
            'operatingSystem' => ['Windows', 'macOS', 'Linux'],
            'applicationCategory' => 'Game',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock'
            ],
            'publisher' => $jsonLdConfig['organization'],
            'datePublished' => '2024-01-01',
            'image' => asset($config['default_image'])
        ];

        // Website structured data
        $websiteData = $jsonLdConfig['website'];

        // Add the structured data to the page
        seo()->addMeta('application/ld+json', json_encode($gameData), 'script');
        seo()->addMeta('application/ld+json', json_encode($websiteData), 'script');
    }

    /**
     * Get optimized images array, filtering out non-existent files
     */
    protected function getOptimizedImages(array $imagePaths): array
    {
        $images = [];
        foreach ($imagePaths as $path) {
            if (file_exists(public_path($path))) {
                $images[] = asset($path);
            }
        }
        
        // Fallback to placeholder if no images exist
        if (empty($images)) {
            $images[] = asset('img/travian/placeholder.svg');
        }
        
        return $images;
    }

    /**
     * Set canonical URL for the current page
     */
    public function setCanonicalUrl(string $url = null): void
    {
        if (!$url) {
            $url = request()->url();
        }
        
        seo()->canonical($url);
    }

    /**
     * Set robots meta tag
     */
    public function setRobotsMeta(array $robots = []): void
    {
        $defaultRobots = config('seo.robots', []);
        $robots = array_merge($defaultRobots, $robots);
        
        $robotsString = implode(', ', array_filter([
            $robots['index'] ? 'index' : 'noindex',
            $robots['follow'] ? 'follow' : 'nofollow',
            $robots['archive'] ? 'archive' : 'noarchive',
            $robots['snippet'] ? 'snippet' : 'nosnippet',
            $robots['imageindex'] ? 'imageindex' : 'noimageindex',
            $robots['nocache'] ? 'nocache' : null,
        ]));
        
        seo()->addMeta('robots', $robotsString);
    }
}
