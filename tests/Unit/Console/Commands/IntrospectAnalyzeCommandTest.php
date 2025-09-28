<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\IntrospectAnalyzeCommand;
use App\Services\IntrospectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

class IntrospectAnalyzeCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_analyze_codebase()
    {
        $this->mock(IntrospectService::class, function ($mock): void {
            $mock->shouldReceive('analyzeCodebase')->andReturn([
                'total_files' => 100,
                'total_lines' => 5000,
                'complexity_score' => 75,
                'maintainability_index' => 80,
                'technical_debt' => 15,
            ]);
        });

        $this
            ->artisan('introspect:analyze')
            ->expectsOutput('🔍 Codebase Analysis')
            ->expectsOutput('Analyzing codebase...')
            ->expectsOutput('Total files: 100')
            ->expectsOutput('Total lines: 5000')
            ->expectsOutput('Complexity score: 75')
            ->expectsOutput('Maintainability index: 80')
            ->expectsOutput('Technical debt: 15')
            ->expectsOutput('Analysis completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_analyze_codebase_with_output_file()
    {
        $this->mock(IntrospectService::class, function ($mock): void {
            $mock->shouldReceive('analyzeCodebase')->andReturn([
                'total_files' => 50,
                'total_lines' => 2500,
                'complexity_score' => 60,
                'maintainability_index' => 85,
                'technical_debt' => 10,
            ]);
        });

        File::shouldReceive('put')->with('analysis.json', Mockery::type('string'))->andReturn(true);

        $this
            ->artisan('introspect:analyze', ['--output' => 'analysis.json'])
            ->expectsOutput('🔍 Codebase Analysis')
            ->expectsOutput('Analyzing codebase...')
            ->expectsOutput('Total files: 50')
            ->expectsOutput('Total lines: 2500')
            ->expectsOutput('Complexity score: 60')
            ->expectsOutput('Maintainability index: 85')
            ->expectsOutput('Technical debt: 10')
            ->expectsOutput('Analysis completed successfully')
            ->expectsOutput('Results saved to: analysis.json')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_analyze_codebase_with_verbose_output()
    {
        $this->mock(IntrospectService::class, function ($mock): void {
            $mock->shouldReceive('analyzeCodebase')->andReturn([
                'total_files' => 25,
                'total_lines' => 1250,
                'complexity_score' => 45,
                'maintainability_index' => 90,
                'technical_debt' => 5,
                'detailed_analysis' => [
                    'controllers' => ['count' => 10, 'complexity' => 30],
                    'models' => ['count' => 15, 'complexity' => 15],
                ],
            ]);
        });

        $this
            ->artisan('introspect:analyze', ['--verbose' => true])
            ->expectsOutput('🔍 Codebase Analysis')
            ->expectsOutput('Analyzing codebase...')
            ->expectsOutput('Total files: 25')
            ->expectsOutput('Total lines: 1250')
            ->expectsOutput('Complexity score: 45')
            ->expectsOutput('Maintainability index: 90')
            ->expectsOutput('Technical debt: 5')
            ->expectsOutput('Detailed analysis:')
            ->expectsOutput('  Controllers: 10 files, complexity: 30')
            ->expectsOutput('  Models: 15 files, complexity: 15')
            ->expectsOutput('Analysis completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_analyze_specific_directory()
    {
        $this->mock(IntrospectService::class, function ($mock): void {
            $mock
                ->shouldReceive('analyzeDirectory')
                ->with('app/Http/Controllers')
                ->andReturn([
                    'total_files' => 20,
                    'total_lines' => 1000,
                    'complexity_score' => 40,
                    'maintainability_index' => 85,
                    'technical_debt' => 8,
                ]);
        });

        $this
            ->artisan('introspect:analyze', ['--directory' => 'app/Http/Controllers'])
            ->expectsOutput('🔍 Codebase Analysis')
            ->expectsOutput('Analyzing directory: app/Http/Controllers')
            ->expectsOutput('Total files: 20')
            ->expectsOutput('Total lines: 1000')
            ->expectsOutput('Complexity score: 40')
            ->expectsOutput('Maintainability index: 85')
            ->expectsOutput('Technical debt: 8')
            ->expectsOutput('Analysis completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_analyze_with_complexity_threshold()
    {
        $this->mock(IntrospectService::class, function ($mock): void {
            $mock
                ->shouldReceive('analyzeCodebase')
                ->with(['complexity_threshold' => 50])
                ->andReturn([
                    'total_files' => 30,
                    'total_lines' => 1500,
                    'complexity_score' => 35,
                    'maintainability_index' => 88,
                    'technical_debt' => 7,
                ]);
        });

        $this
            ->artisan('introspect:analyze', ['--complexity-threshold' => '50'])
            ->expectsOutput('🔍 Codebase Analysis')
            ->expectsOutput('Analyzing codebase...')
            ->expectsOutput('Total files: 30')
            ->expectsOutput('Total lines: 1500')
            ->expectsOutput('Complexity score: 35')
            ->expectsOutput('Maintainability index: 88')
            ->expectsOutput('Technical debt: 7')
            ->expectsOutput('Analysis completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_analyze_with_exclude_patterns()
    {
        $this->mock(IntrospectService::class, function ($mock): void {
            $mock
                ->shouldReceive('analyzeCodebase')
                ->with(['exclude_patterns' => ['vendor/*', 'tests/*']])
                ->andReturn([
                    'total_files' => 40,
                    'total_lines' => 2000,
                    'complexity_score' => 50,
                    'maintainability_index' => 82,
                    'technical_debt' => 12,
                ]);
        });

        $this
            ->artisan('introspect:analyze', ['--exclude' => 'vendor/*,tests/*'])
            ->expectsOutput('🔍 Codebase Analysis')
            ->expectsOutput('Analyzing codebase...')
            ->expectsOutput('Total files: 40')
            ->expectsOutput('Total lines: 2000')
            ->expectsOutput('Complexity score: 50')
            ->expectsOutput('Maintainability index: 82')
            ->expectsOutput('Technical debt: 12')
            ->expectsOutput('Analysis completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_analysis_failure()
    {
        $this->mock(IntrospectService::class, function ($mock): void {
            $mock->shouldReceive('analyzeCodebase')->andThrow(new \Exception('Analysis failed'));
        });

        $this
            ->artisan('introspect:analyze')
            ->expectsOutput('🔍 Codebase Analysis')
            ->expectsOutput('Analyzing codebase...')
            ->expectsOutput('Analysis failed: Analysis failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_output_file_write_failure()
    {
        $this->mock(IntrospectService::class, function ($mock): void {
            $mock->shouldReceive('analyzeCodebase')->andReturn([
                'total_files' => 10,
                'total_lines' => 500,
                'complexity_score' => 30,
                'maintainability_index' => 90,
                'technical_debt' => 3,
            ]);
        });

        File::shouldReceive('put')->andThrow(new \Exception('File write failed'));

        $this
            ->artisan('introspect:analyze', ['--output' => 'analysis.json'])
            ->expectsOutput('🔍 Codebase Analysis')
            ->expectsOutput('Analyzing codebase...')
            ->expectsOutput('Total files: 10')
            ->expectsOutput('Total lines: 500')
            ->expectsOutput('Complexity score: 30')
            ->expectsOutput('Maintainability index: 90')
            ->expectsOutput('Technical debt: 3')
            ->expectsOutput('Analysis completed successfully')
            ->expectsOutput('Failed to save results: File write failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_analyze_with_format_options()
    {
        $this->mock(IntrospectService::class, function ($mock): void {
            $mock->shouldReceive('analyzeCodebase')->andReturn([
                'total_files' => 15,
                'total_lines' => 750,
                'complexity_score' => 25,
                'maintainability_index' => 95,
                'technical_debt' => 2,
            ]);
        });

        $this
            ->artisan('introspect:analyze', ['--format' => 'json'])
            ->expectsOutput('🔍 Codebase Analysis')
            ->expectsOutput('Analyzing codebase...')
            ->expectsOutput('Total files: 15')
            ->expectsOutput('Total lines: 750')
            ->expectsOutput('Complexity score: 25')
            ->expectsOutput('Maintainability index: 95')
            ->expectsOutput('Technical debt: 2')
            ->expectsOutput('Analysis completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_analyze_with_metrics_only()
    {
        $this->mock(IntrospectService::class, function ($mock): void {
            $mock
                ->shouldReceive('analyzeCodebase')
                ->with(['metrics_only' => true])
                ->andReturn([
                    'total_files' => 5,
                    'total_lines' => 250,
                    'complexity_score' => 15,
                    'maintainability_index' => 98,
                    'technical_debt' => 1,
                ]);
        });

        $this
            ->artisan('introspect:analyze', ['--metrics-only' => true])
            ->expectsOutput('🔍 Codebase Analysis')
            ->expectsOutput('Analyzing codebase...')
            ->expectsOutput('Total files: 5')
            ->expectsOutput('Total lines: 250')
            ->expectsOutput('Complexity score: 15')
            ->expectsOutput('Maintainability index: 98')
            ->expectsOutput('Technical debt: 1')
            ->expectsOutput('Analysis completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new IntrospectAnalyzeCommand();
        $this->assertEquals('introspect:analyze', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new IntrospectAnalyzeCommand();
        $this->assertEquals('Analyze codebase complexity and maintainability', $command->getDescription());
    }
}
