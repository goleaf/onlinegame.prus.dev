<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeoIntegrationStatus extends Command
{
    protected $signature = 'seo:status';

    protected $description = 'Check SEO integration status and health';

    public function handle()
    {
        $this->info('🔍 SEO Integration Status Report');
        $this->newLine();

        $this->checkConfiguration();
        $this->checkFiles();
        $this->checkServices();
        $this->checkCommands();
        $this->checkImages();
        $this->generateSummary();

        return 0;
    }

    protected function checkConfiguration(): void
    {
        $this->info('⚙️  Configuration Check:');

        $configExists = file_exists(config_path('seo.php'));
        $this->line($configExists ? '  ✅ SEO config file exists' : '  ❌ SEO config file missing');

        if ($configExists) {
            $config = config('seo');
            $required = ['default_title', 'default_description', 'site_name'];
            foreach ($required as $key) {
                $exists = isset($config[$key]) && ! empty($config[$key]);
                $this->line($exists ? "  ✅ {$key} configured" : "  ❌ {$key} missing");
            }
        }

        $this->newLine();
    }

    protected function checkFiles(): void
    {
        $this->info('📁 File Structure Check:');

        $files = [
            'public/sitemap.xml' => 'Sitemap file',
            'public/robots.txt' => 'Robots.txt file',
            'app/Services/GameSeoService.php' => 'Main SEO service',
            'app/Services/SeoCacheService.php' => 'SEO cache service',
            'app/Services/SeoBreadcrumbService.php' => 'SEO breadcrumb service',
            'app/Services/SeoAnalyticsService.php' => 'SEO analytics service',
            'app/Http/Middleware/SeoMiddleware.php' => 'SEO middleware',
            'app/Helpers/SeoHelper.php' => 'SEO helper functions',
        ];

        foreach ($files as $file => $description) {
            $exists = file_exists(base_path($file));
            $this->line($exists ? "  ✅ {$description}" : "  ❌ {$description} missing");
        }

        $this->newLine();
    }

    protected function checkServices(): void
    {
        $this->info('🔧 Service Integration Check:');

        $services = [
            'GameSeoService' => 'Main SEO service',
            'SeoCacheService' => 'Caching service',
            'SeoBreadcrumbService' => 'Breadcrumb service',
            'SeoAnalyticsService' => 'Analytics service',
        ];

        foreach ($services as $service => $description) {
            $exists = class_exists("App\\Services\\{$service}");
            $this->line($exists ? "  ✅ {$description}" : "  ❌ {$description} missing");
        }

        $this->newLine();
    }

    protected function checkCommands(): void
    {
        $this->info('⌨️  Command Check:');

        $commands = [
            'seo:generate-sitemap' => 'Sitemap generation',
            'seo:validate' => 'SEO validation',
        ];

        foreach ($commands as $command => $description) {
            $output = shell_exec("php artisan list | grep '{$command}' 2>/dev/null");
            $exists = ! empty($output);
            $this->line($exists ? "  ✅ {$description}" : "  ❌ {$description} missing");
        }

        $this->newLine();
    }

    protected function checkImages(): void
    {
        $this->info('🖼️  SEO Images Check:');

        $seoDir = public_path('img/travian');
        if (is_dir($seoDir)) {
            $images = glob($seoDir.'/*.{svg,png,jpg,jpeg}', GLOB_BRACE);
            $this->line('  ✅ SEO images directory exists');
            $this->line('  📊 Total images: '.count($images));

            $requiredImages = ['game-logo.svg', 'village-preview.svg', 'world-map.svg', 'placeholder.svg'];
            foreach ($requiredImages as $image) {
                $exists = file_exists($seoDir.'/'.$image);
                $this->line($exists ? "  ✅ {$image}" : "  ❌ {$image} missing");
            }
        } else {
            $this->error('  ❌ SEO images directory not found');
        }

        $this->newLine();
    }

    protected function generateSummary(): void
    {
        $this->info('📊 SEO Integration Summary:');

        $checks = [
            'Configuration' => file_exists(config_path('seo.php')),
            'Main Service' => file_exists(app_path('Services/GameSeoService.php')),
            'Cache Service' => file_exists(app_path('Services/SeoCacheService.php')),
            'Breadcrumb Service' => file_exists(app_path('Services/SeoBreadcrumbService.php')),
            'Analytics Service' => file_exists(app_path('Services/SeoAnalyticsService.php')),
            'Middleware' => file_exists(app_path('Http/Middleware/SeoMiddleware.php')),
            'Helper Functions' => file_exists(app_path('Helpers/SeoHelper.php')),
            'Sitemap' => file_exists(public_path('sitemap.xml')),
            'Robots.txt' => file_exists(public_path('robots.txt')),
            'SEO Images' => is_dir(public_path('img/travian')),
        ];

        $passed = array_sum($checks);
        $total = count($checks);
        $percentage = round(($passed / $total) * 100);

        $this->line("  📈 Integration Status: {$passed}/{$total} ({$percentage}%)");

        if ($percentage === 100) {
            $this->line('  🎉 SEO integration is complete and ready for production!');
        } elseif ($percentage >= 80) {
            $this->line('  ✅ SEO integration is mostly complete with minor issues');
        } elseif ($percentage >= 60) {
            $this->line('  ⚠️  SEO integration has some missing components');
        } else {
            $this->line('  ❌ SEO integration needs significant work');
        }

        $this->newLine();
        $this->line('🚀 SEO Integration Status Report completed at '.now()->format('Y-m-d H:i:s'));
    }
}
