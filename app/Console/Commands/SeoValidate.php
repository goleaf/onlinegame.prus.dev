<?php

namespace App\Console\Commands;

use App\Helpers\SeoHelper;
use Illuminate\Console\Command;

class SeoValidate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:validate {--url=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate SEO metadata for pages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->option('url');
        $all = $this->option('all');

        $this->info('Validating SEO metadata...');

        if ($url) {
            $this->validateUrl($url);
        } elseif ($all) {
            $this->validateAllPages();
        } else {
            $this->validateMainPages();
        }

        return 0;
    }

    /**
     * Validate a specific URL
     */
    protected function validateUrl(string $url): void
    {
        $this->info("Validating URL: {$url}");

        // This would require making HTTP requests to validate actual metadata
        // For now, we'll validate the configuration
        $this->validateConfiguration();
    }

    /**
     * Validate main pages
     */
    protected function validateMainPages(): void
    {
        $pages = [
            'Home' => [
                'title' => config('seo.default_title'),
                'description' => config('seo.default_description'),
                'image' => asset(config('seo.default_image'))
            ],
            'Game Index' => [
                'title' => config('seo.default_title'),
                'description' => config('seo.default_description'),
                'image' => asset(config('seo.default_image'))
            ]
        ];

        $this->validatePageMetadata($pages);
    }

    /**
     * Validate all pages (placeholder)
     */
    protected function validateAllPages(): void
    {
        $this->info('Validating all pages...');
        $this->validateMainPages();
        $this->validateConfiguration();
    }

    /**
     * Validate page metadata
     */
    protected function validatePageMetadata(array $pages): void
    {
        $totalErrors = 0;

        foreach ($pages as $pageName => $metadata) {
            $this->info("\nValidating: {$pageName}");

            $errors = SeoHelper::validate($metadata);

            if (empty($errors)) {
                $this->info('  ✅ All SEO metadata is valid');
            } else {
                $this->error('  ❌ Found ' . count($errors) . ' issues:');
                foreach ($errors as $error) {
                    $this->error("    - {$error}");
                }
                $totalErrors += count($errors);
            }
        }

        $this->newLine();
        if ($totalErrors === 0) {
            $this->info('🎉 All SEO metadata is valid!');
        } else {
            $this->error("Found {$totalErrors} total SEO issues that need to be fixed.");
        }
    }

    /**
     * Validate SEO configuration
     */
    protected function validateConfiguration(): void
    {
        $this->info("\nValidating SEO configuration...");

        $config = config('seo');
        $errors = [];

        // Check required configuration
        if (empty($config['default_title'])) {
            $errors[] = 'Default title is not set';
        }

        if (empty($config['default_description'])) {
            $errors[] = 'Default description is not set';
        }

        if (empty($config['default_image'])) {
            $errors[] = 'Default image is not set';
        } elseif (!file_exists(public_path($config['default_image']))) {
            $errors[] = 'Default image file does not exist: ' . $config['default_image'];
        }

        if (empty($config['site_name'])) {
            $errors[] = 'Site name is not set';
        }

        // Check Twitter configuration
        if (empty($config['twitter']['site'])) {
            $errors[] = 'Twitter site handle is not set';
        }

        if (empty($errors)) {
            $this->info('  ✅ SEO configuration is valid');
        } else {
            $this->error('  ❌ Found ' . count($errors) . ' configuration issues:');
            foreach ($errors as $error) {
                $this->error("    - {$error}");
            }
        }
    }
}
