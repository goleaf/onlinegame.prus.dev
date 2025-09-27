<?php

namespace App\Console\Commands;

use App\Models\Game\World;
use App\Models\Game\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:generate-sitemap {--output=public/sitemap.xml}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate XML sitemap for SEO optimization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $outputPath = $this->option('output');
        $baseUrl = config('app.url');

        $this->info('Generating sitemap...');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Static pages
        $staticPages = [
            ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => '/game', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/game/map', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['url' => '/game/features', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/login', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => '/register', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ];

        foreach ($staticPages as $page) {
            $xml .= $this->generateUrlEntry($baseUrl . $page['url'], $page['priority'], $page['changefreq']);
        }

        // World pages (if any) - Ready for future enhancement
        // World model integration ready for future enhancement
        /*
        try {
            $worlds = World::all();
            foreach ($worlds as $world) {
                $xml .= $this->generateUrlEntry(
                    $baseUrl . '/game/world/' . $world->id,
                    '0.7',
                    'weekly',
                    $world->updated_at ?? now()
                );
            }
        } catch (\Exception $e) {
            $this->warn('Could not fetch worlds for sitemap: ' . $e->getMessage());
            $worlds = collect(); // Empty collection for count
        }
        */

        // Public player profiles (if any exist and are public)
        // Note: Commenting out until is_public column is added to players table
        /*
        $publicPlayers = Player::where('is_public', true)->limit(100)->get();
        foreach ($publicPlayers as $player) {
            $xml .= $this->generateUrlEntry(
                $baseUrl . '/game/player/' . $player->id,
                '0.5',
                'weekly',
                $player->updated_at
            );
        }
        */

        $xml .= '</urlset>' . PHP_EOL;

        // Write the sitemap to file
        File::put($outputPath, $xml);

        $this->info("Sitemap generated successfully at: {$outputPath}");
        $this->info('Total URLs: ' . count($staticPages));

        return 0;
    }

    /**
     * Generate a URL entry for the sitemap
     */
    private function generateUrlEntry(string $url, string $priority, string $changefreq, $lastmod = null): string
    {
        $xml = '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
        
        if ($lastmod) {
            $xml .= '    <lastmod>' . $lastmod->format('Y-m-d') . '</lastmod>' . PHP_EOL;
        } else {
            $xml .= '    <lastmod>' . now()->format('Y-m-d') . '</lastmod>' . PHP_EOL;
        }
        
        $xml .= '    <changefreq>' . $changefreq . '</changefreq>' . PHP_EOL;
        $xml .= '    <priority>' . $priority . '</priority>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;

        return $xml;
    }
}