<?php

namespace App\Console\Commands;

use App\Services\IntrospectService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class IntrospectAnalyzeCommand extends Command
{
    protected $signature = 'introspect:analyze
        {--output= : Save analysis results to the given file}
        {--format=json : Output format when saving results (json, yaml, array)}
        {--directory= : Analyze a specific directory}
        {--complexity-threshold= : Only include items above this complexity}
        {--exclude= : Comma separated list of paths to exclude}
        {--metrics-only : Return only aggregate metrics}';

    protected $description = 'Analyze codebase complexity and maintainability';

    public function handle(IntrospectService $service): int
    {
        $this->info('🔍 Codebase Analysis');

        $options = $this->buildOptions();
        $verbose = (bool) $this->option('verbose');

        try {
            if (isset($options['directory'])) {
                $directory = $options['directory'];
                unset($options['directory']);
                $this->line("Analyzing directory: {$directory}");
                $results = $service->analyzeDirectory($directory);
            } else {
                $this->line('Analyzing codebase...');
                $results = empty($options)
                    ? $service->analyzeCodebase()
                    : $service->analyzeCodebase($options);
            }
        } catch (\Throwable $exception) {
            $this->error('Analysis failed: '.$exception->getMessage());

            return Command::FAILURE;
        }

        $this->displayMetrics($results, $verbose);
        $this->info('Analysis completed successfully');

        if ($outputPath = $this->option('output')) {
            try {
                $this->writeResults($results, $outputPath, $this->option('format'));
            } catch (\Throwable $exception) {
                $this->error('Failed to save results: '.$exception->getMessage());

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    private function buildOptions(): array
    {
        $options = [];

        if ($directory = $this->option('directory')) {
            $options['directory'] = $directory;
        }

        if ($threshold = $this->option('complexity-threshold')) {
            $options['complexity_threshold'] = (int) $threshold;
        }

        if ($exclude = $this->option('exclude')) {
            $options['exclude_patterns'] = array_values(array_filter(array_map('trim', explode(',', $exclude))));
        }

        if ($this->option('metrics-only')) {
            $options['metrics_only'] = true;
        }

        return $options;
    }

    private function displayMetrics(array $results, bool $verbose): void
    {
        $this->line('Total files: '.($results['total_files'] ?? 0));
        $this->line('Total lines: '.($results['total_lines'] ?? 0));
        $this->line('Complexity score: '.($results['complexity_score'] ?? 0));
        $this->line('Maintainability index: '.($results['maintainability_index'] ?? 0));
        $this->line('Technical debt: '.($results['technical_debt'] ?? 0));

        if ($verbose && isset($results['detailed_analysis']) && is_array($results['detailed_analysis'])) {
            $this->line('Detailed analysis:');

            foreach ($results['detailed_analysis'] as $section => $details) {
                $count = $details['count'] ?? 0;
                $complexity = $details['complexity'] ?? 0;
                $title = ucwords(str_replace('_', ' ', $section));
                $this->line(sprintf('  %s: %d files, complexity: %s', $title, $count, $complexity));
            }
        }
    }

    private function writeResults(array $results, string $path, ?string $format): void
    {
        $format = strtolower($format ?? 'json');

        $content = match ($format) {
            'array' => var_export($results, true),
            'yaml' => function_exists('yaml_emit')
                ? yaml_emit($results)
                : var_export($results, true),
            default => json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        };

        File::put($path, $content);
        $this->info('Results saved to: '.$path);
    }
}
