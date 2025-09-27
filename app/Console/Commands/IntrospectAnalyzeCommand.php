<?php

namespace App\Console\Commands;

use App\Services\IntrospectService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class IntrospectAnalyzeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'introspect:analyze 
                            {--output= : Output file path for the analysis results}
                            {--format=json : Output format (json, yaml, array)}
                            {--models : Analyze models only}
                            {--routes : Analyze routes only}
                            {--views : Analyze views only}
                            {--classes : Analyze classes only}
                            {--schemas : Generate model schemas only}
                            {--dependencies : Analyze model dependencies only}
                            {--performance : Get performance metrics only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze the codebase using Laravel Introspect';

    /**
     * Execute the console command.
     */
    public function handle(IntrospectService $introspectService): int
    {
        $this->info('🔍 Starting Laravel Introspect Analysis...');

        $output = $this->option('output');
        $format = $this->option('format');

        $results = [];

        // Analyze models
        if ($this->option('models') || !$this->hasSpecificOptions()) {
            $this->info('📊 Analyzing models...');
            $results['models'] = $introspectService->getModelAnalysis();
            $this->line('   ✓ Models analyzed: ' . count($results['models']));
        }

        // Analyze routes
        if ($this->option('routes') || !$this->hasSpecificOptions()) {
            $this->info('🛣️  Analyzing routes...');
            $results['routes'] = $introspectService->getRouteAnalysis();
            $this->line('   ✓ Game routes: ' . count($results['routes']['game_routes'] ?? []));
            $this->line('   ✓ API routes: ' . count($results['routes']['api_routes'] ?? []));
        }

        // Analyze views
        if ($this->option('views') || !$this->hasSpecificOptions()) {
            $this->info('👁️  Analyzing views...');
            $results['views'] = $introspectService->getViewAnalysis();
            $this->line('   ✓ Game views: ' . count($results['views']['game_views'] ?? []));
            $this->line('   ✓ Livewire views: ' . count($results['views']['livewire_views'] ?? []));
        }

        // Analyze classes
        if ($this->option('classes') || !$this->hasSpecificOptions()) {
            $this->info('🏗️  Analyzing classes...');
            $results['classes'] = $introspectService->getClassAnalysis();
            $this->line('   ✓ Game controllers: ' . count($results['classes']['game_controllers'] ?? []));
            $this->line('   ✓ Game services: ' . count($results['classes']['game_services'] ?? []));
            $this->line('   ✓ Livewire components: ' . count($results['classes']['livewire_components'] ?? []));
        }

        // Generate schemas
        if ($this->option('schemas') || !$this->hasSpecificOptions()) {
            $this->info('📋 Generating model schemas...');
            $results['schemas'] = $introspectService->getModelSchemas();
            $this->line('   ✓ Schemas generated: ' . count($results['schemas']));
        }

        // Analyze dependencies
        if ($this->option('dependencies') || !$this->hasSpecificOptions()) {
            $this->info('🔗 Analyzing model dependencies...');
            $results['dependencies'] = $introspectService->getModelDependencies();
            $this->line('   ✓ Dependencies analyzed: ' . count($results['dependencies']));
        }

        // Get performance metrics
        if ($this->option('performance') || !$this->hasSpecificOptions()) {
            $this->info('⚡ Calculating performance metrics...');
            $results['performance'] = $introspectService->getModelPerformanceMetrics();
            $this->line('   ✓ Performance metrics calculated: ' . count($results['performance']));
        }

        // Add metadata
        $results['metadata'] = [
            'generated_at' => now()->toISOString(),
            'laravel_version' => app()->version(),
            'introspect_version' => '1.1.2',
            'analysis_type' => $this->getAnalysisType(),
        ];

        // Output results
        if ($output) {
            $this->saveResults($results, $output, $format);
        } else {
            $this->displayResults($results, $format);
        }

        $this->info('✅ Analysis completed successfully!');
        return Command::SUCCESS;
    }

    /**
     * Check if specific analysis options are provided
     */
    private function hasSpecificOptions(): bool
    {
        return $this->option('models') ||
            $this->option('routes') ||
            $this->option('views') ||
            $this->option('classes') ||
            $this->option('schemas') ||
            $this->option('dependencies') ||
            $this->option('performance');
    }

    /**
     * Get analysis type description
     */
    private function getAnalysisType(): string
    {
        $types = [];

        if ($this->option('models'))
            $types[] = 'models';
        if ($this->option('routes'))
            $types[] = 'routes';
        if ($this->option('views'))
            $types[] = 'views';
        if ($this->option('classes'))
            $types[] = 'classes';
        if ($this->option('schemas'))
            $types[] = 'schemas';
        if ($this->option('dependencies'))
            $types[] = 'dependencies';
        if ($this->option('performance'))
            $types[] = 'performance';

        return empty($types) ? 'comprehensive' : implode(', ', $types);
    }

    /**
     * Save results to file
     */
    private function saveResults(array $results, string $output, string $format): void
    {
        $content = match ($format) {
            'yaml' => yaml_emit($results),
            'array' => var_export($results, true),
            default => json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        };

        File::put($output, $content);
        $this->info("📁 Results saved to: {$output}");
    }

    /**
     * Display results in console
     */
    private function displayResults(array $results, string $format): void
    {
        if ($format === 'json') {
            $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Component', 'Count', 'Details'],
                $this->formatResultsForTable($results)
            );
        }
    }

    /**
     * Format results for table display
     */
    private function formatResultsForTable(array $results): array
    {
        $table = [];

        foreach ($results as $key => $value) {
            if ($key === 'metadata')
                continue;

            if (is_array($value)) {
                $count = count($value);
                $details = $this->getDetailsForComponent($key, $value);
                $table[] = [ucfirst($key), $count, $details];
            }
        }

        return $table;
    }

    /**
     * Get details for a specific component
     */
    private function getDetailsForComponent(string $component, array $data): string
    {
        return match ($component) {
            'models' => 'Game models analyzed',
            'routes' => 'Game and API routes',
            'views' => 'Game and Livewire views',
            'classes' => 'Controllers, services, components',
            'schemas' => 'JSON schemas generated',
            'dependencies' => 'Model relationships',
            'performance' => 'Complexity metrics',
            default => 'Analysis completed',
        };
    }
}
