<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\LoggingUtil;

class SeoAnalyticsService
{
    protected CachingUtil $cachingUtil;
    protected LoggingUtil $loggingUtil;

    public function __construct()
    {
        $this->cachingUtil = new CachingUtil(3600, ['seo_analytics']);
        $this->loggingUtil = new LoggingUtil();
    }
    /**
     * Track SEO metrics for a page
     */
    public function trackPageMetrics(string $page, array $metadata): void
    {
        $metrics = [
            'page' => $page,
            'timestamp' => now(),
            'title_length' => strlen($metadata['title'] ?? ''),
            'description_length' => strlen($metadata['description'] ?? ''),
            'has_images' => !empty($metadata['images']),
            'image_count' => count($metadata['images'] ?? []),
            'has_keywords' => !empty($metadata['keywords']),
            'keyword_count' => count($metadata['keywords'] ?? []),
            'has_structured_data' => !empty($metadata['structured_data']),
        ];

        $this->storeMetrics($page, $metrics);
    }

    /**
     * Store metrics in cache
     */
    protected function storeMetrics(string $page, array $metrics): void
    {
        $key = "seo_metrics_{$page}_" . date('Y-m-d');
        $existingMetrics = Cache::get($key, []);
        $existingMetrics[] = $metrics;
        
        // Keep only last 100 entries per day
        if (count($existingMetrics) > 100) {
            $existingMetrics = array_slice($existingMetrics, -100);
        }
        
        Cache::put($key, $existingMetrics, 86400); // 24 hours
    }

    /**
     * Get SEO performance report
     */
    public function getPerformanceReport(string $page = null): array
    {
        if ($page) {
            return $this->getPageReport($page);
        }

        return $this->getOverallReport();
    }

    /**
     * Get report for specific page
     */
    protected function getPageReport(string $page): array
    {
        $key = "seo_metrics_{$page}_" . date('Y-m-d');
        $metrics = Cache::get($key, []);

        if (empty($metrics)) {
            return [
                'page' => $page,
                'date' => date('Y-m-d'),
                'total_requests' => 0,
                'average_title_length' => 0,
                'average_description_length' => 0,
                'image_usage_percentage' => 0,
                'structured_data_usage_percentage' => 0,
            ];
        }

        $totalRequests = count($metrics);
        $titleLengths = array_column($metrics, 'title_length');
        $descriptionLengths = array_column($metrics, 'description_length');
        $hasImages = array_column($metrics, 'has_images');
        $hasStructuredData = array_column($metrics, 'has_structured_data');

        return [
            'page' => $page,
            'date' => date('Y-m-d'),
            'total_requests' => $totalRequests,
            'average_title_length' => round(array_sum($titleLengths) / $totalRequests, 2),
            'average_description_length' => round(array_sum($descriptionLengths) / $totalRequests, 2),
            'image_usage_percentage' => round((array_sum($hasImages) / $totalRequests) * 100, 2),
            'structured_data_usage_percentage' => round((array_sum($hasStructuredData) / $totalRequests) * 100, 2),
            'metrics' => $metrics,
        ];
    }

    /**
     * Get overall SEO report
     */
    protected function getOverallReport(): array
    {
        $pages = ['home', 'game', 'dashboard', 'village', 'map'];
        $overallMetrics = [];

        foreach ($pages as $page) {
            $key = "seo_metrics_{$page}_" . date('Y-m-d');
            $metrics = Cache::get($key, []);
            
            if (!empty($metrics)) {
                $overallMetrics[$page] = [
                    'total_requests' => count($metrics),
                    'average_title_length' => round(array_sum(array_column($metrics, 'title_length')) / count($metrics), 2),
                    'average_description_length' => round(array_sum(array_column($metrics, 'description_length')) / count($metrics), 2),
                ];
            }
        }

        return [
            'date' => date('Y-m-d'),
            'pages' => $overallMetrics,
            'total_pages_tracked' => count($overallMetrics),
            'total_requests' => array_sum(array_column($overallMetrics, 'total_requests')),
        ];
    }

    /**
     * Validate SEO metadata and return issues
     */
    public function validateSeoMetadata(array $metadata): array
    {
        $issues = [];

        // Title validation
        if (empty($metadata['title'])) {
            $issues[] = 'Title is missing';
        } elseif (strlen($metadata['title']) < 10) {
            $issues[] = 'Title is too short (minimum 10 characters)';
        } elseif (strlen($metadata['title']) > 70) {
            $issues[] = 'Title is too long (maximum 70 characters)';
        }

        // Description validation
        if (empty($metadata['description'])) {
            $issues[] = 'Description is missing';
        } elseif (strlen($metadata['description']) < 50) {
            $issues[] = 'Description is too short (minimum 50 characters)';
        } elseif (strlen($metadata['description']) > 160) {
            $issues[] = 'Description is too long (maximum 160 characters)';
        }

        // Keywords validation
        if (empty($metadata['keywords'])) {
            $issues[] = 'Keywords are missing';
        } elseif (count($metadata['keywords']) < 3) {
            $issues[] = 'Too few keywords (minimum 3 recommended)';
        }

        // Images validation
        if (empty($metadata['images'])) {
            $issues[] = 'Images are missing';
        }

        return $issues;
    }

    /**
     * Get SEO recommendations based on analytics
     */
    public function getSeoRecommendations(): array
    {
        $recommendations = [];
        $report = $this->getOverallReport();

        if ($report['total_requests'] < 10) {
            $recommendations[] = 'Increase page traffic to get more reliable SEO metrics';
        }

        foreach ($report['pages'] as $page => $metrics) {
            if ($metrics['average_title_length'] < 30) {
                $recommendations[] = "Consider making {$page} titles more descriptive (current: {$metrics['average_title_length']} chars)";
            }

            if ($metrics['average_description_length'] < 100) {
                $recommendations[] = "Consider expanding {$page} descriptions (current: {$metrics['average_description_length']} chars)";
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = 'SEO metadata looks good! Consider adding more structured data for better search visibility.';
        }

        return $recommendations;
    }

    /**
     * Clear old metrics data
     */
    public function clearOldMetrics(int $daysToKeep = 30): void
    {
        $cutoffDate = now()->subDays($daysToKeep);
        $pages = ['home', 'game', 'dashboard', 'village', 'map'];

        foreach ($pages as $page) {
            $currentDate = now();
            while ($currentDate->gte($cutoffDate)) {
                $key = "seo_metrics_{$page}_" . $currentDate->format('Y-m-d');
                Cache::forget($key);
                $currentDate->subDay();
            }
        }

        Log::info("Cleared SEO metrics older than {$daysToKeep} days");
    }

    /**
     * Export SEO metrics to array
     */
    public function exportMetrics(string $startDate = null, string $endDate = null): array
    {
        $startDate = $startDate ?: now()->subDays(7)->format('Y-m-d');
        $endDate = $endDate ?: now()->format('Y-m-d');
        
        $pages = ['home', 'game', 'dashboard', 'village', 'map'];
        $exportData = [];

        foreach ($pages as $page) {
            $currentDate = \Carbon\Carbon::parse($startDate);
            $endDateObj = \Carbon\Carbon::parse($endDate);
            
            while ($currentDate->lte($endDateObj)) {
                $key = "seo_metrics_{$page}_" . $currentDate->format('Y-m-d');
                $metrics = Cache::get($key, []);
                
                if (!empty($metrics)) {
                    $exportData[$page][$currentDate->format('Y-m-d')] = $metrics;
                }
                
                $currentDate->addDay();
            }
        }

        return $exportData;
    }
}
