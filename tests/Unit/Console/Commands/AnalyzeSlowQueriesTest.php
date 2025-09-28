<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\AnalyzeSlowQueries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalyzeSlowQueriesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_analyze_slow_queries()
    {
        // Mock slow query log data
        Storage::fake('local');
        Storage::put('slow-query.log', "
# Time: 2023-01-01T10:00:00.000000Z
# User@Host: root[root] @ localhost []  Id:     1
# Query_time: 2.500000  Lock_time: 0.000000 Rows_sent: 100  Rows_examined: 10000
use test_db;
SELECT * FROM users WHERE name LIKE '%test%';

# Time: 2023-01-01T10:01:00.000000Z
# User@Host: root[root] @ localhost []  Id:     2
# Query_time: 1.800000  Lock_time: 0.100000 Rows_sent: 50  Rows_examined: 5000
use test_db;
SELECT * FROM villages WHERE player_id = 1;
        ");

        $this
            ->artisan('queries:analyze-slow')
            ->expectsOutput('Analyzing slow queries...')
            ->expectsOutput('=== Slow Query Analysis ===')
            ->expectsOutput('Total slow queries found: 2')
            ->expectsOutput('Average query time: 2.15 seconds')
            ->expectsOutput('Slowest query: 2.50 seconds')
            ->expectsOutput('=== Top 5 Slowest Queries ===')
            ->expectsOutput('Query Time: 2.50s | Rows Examined: 10000 | Rows Sent: 100')
            ->expectsOutput("Query: SELECT * FROM users WHERE name LIKE '%test%';")
            ->expectsOutput('Query Time: 1.80s | Rows Examined: 5000 | Rows Sent: 50')
            ->expectsOutput('Query: SELECT * FROM villages WHERE player_id = 1;')
            ->expectsOutput('=== Recommendations ===')
            ->expectsOutput('Analysis completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_analyze_slow_queries_with_threshold()
    {
        Storage::fake('local');
        Storage::put('slow-query.log', "
# Time: 2023-01-01T10:00:00.000000Z
# User@Host: root[root] @ localhost []  Id:     1
# Query_time: 2.500000  Lock_time: 0.000000 Rows_sent: 100  Rows_examined: 10000
use test_db;
SELECT * FROM users WHERE name LIKE '%test%';

# Time: 2023-01-01T10:01:00.000000Z
# User@Host: root[root] @ localhost []  Id:     2
# Query_time: 1.200000  Lock_time: 0.100000 Rows_sent: 50  Rows_examined: 5000
use test_db;
SELECT * FROM villages WHERE player_id = 1;
        ");

        $this
            ->artisan('queries:analyze-slow', ['--threshold' => '2.0'])
            ->expectsOutput('Analyzing slow queries...')
            ->expectsOutput('Using threshold: 2.0 seconds')
            ->expectsOutput('=== Slow Query Analysis ===')
            ->expectsOutput('Total slow queries found: 1')
            ->expectsOutput('Average query time: 2.50 seconds')
            ->expectsOutput('Slowest query: 2.50 seconds')
            ->expectsOutput('=== Top 5 Slowest Queries ===')
            ->expectsOutput('Query Time: 2.50s | Rows Examined: 10000 | Rows Sent: 100')
            ->expectsOutput("Query: SELECT * FROM users WHERE name LIKE '%test%';")
            ->expectsOutput('=== Recommendations ===')
            ->expectsOutput('Analysis completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_analyze_slow_queries_with_export()
    {
        Storage::fake('local');
        Storage::put('slow-query.log', "
# Time: 2023-01-01T10:00:00.000000Z
# User@Host: root[root] @ localhost []  Id:     1
# Query_time: 2.500000  Lock_time: 0.000000 Rows_sent: 100  Rows_examined: 10000
use test_db;
SELECT * FROM users WHERE name LIKE '%test%';
        ");

        $this
            ->artisan('queries:analyze-slow', ['--export' => true])
            ->expectsOutput('Analyzing slow queries...')
            ->expectsOutput('=== Slow Query Analysis ===')
            ->expectsOutput('Total slow queries found: 1')
            ->expectsOutput('Average query time: 2.50 seconds')
            ->expectsOutput('Slowest query: 2.50 seconds')
            ->expectsOutput('=== Top 5 Slowest Queries ===')
            ->expectsOutput('Query Time: 2.50s | Rows Examined: 10000 | Rows Sent: 100')
            ->expectsOutput("Query: SELECT * FROM users WHERE name LIKE '%test%';")
            ->expectsOutput('=== Recommendations ===')
            ->expectsOutput('Exporting analysis to CSV...')
            ->expectsOutput('Analysis exported to: ')
            ->expectsOutput('Analysis completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_missing_slow_query_log()
    {
        Storage::fake('local');

        $this
            ->artisan('queries:analyze-slow')
            ->expectsOutput('Analyzing slow queries...')
            ->expectsOutput('Slow query log file not found.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_empty_slow_query_log()
    {
        Storage::fake('local');
        Storage::put('slow-query.log', '');

        $this
            ->artisan('queries:analyze-slow')
            ->expectsOutput('Analyzing slow queries...')
            ->expectsOutput('=== Slow Query Analysis ===')
            ->expectsOutput('Total slow queries found: 0')
            ->expectsOutput('No slow queries found in the log.')
            ->expectsOutput('Analysis completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_analyze_slow_queries_with_detailed_output()
    {
        Storage::fake('local');
        Storage::put('slow-query.log', "
# Time: 2023-01-01T10:00:00.000000Z
# User@Host: root[root] @ localhost []  Id:     1
# Query_time: 2.500000  Lock_time: 0.000000 Rows_sent: 100  Rows_examined: 10000
use test_db;
SELECT * FROM users WHERE name LIKE '%test%';
        ");

        $this
            ->artisan('queries:analyze-slow', ['--detailed' => true])
            ->expectsOutput('Analyzing slow queries...')
            ->expectsOutput('=== Slow Query Analysis ===')
            ->expectsOutput('Total slow queries found: 1')
            ->expectsOutput('Average query time: 2.50 seconds')
            ->expectsOutput('Slowest query: 2.50 seconds')
            ->expectsOutput('=== Top 5 Slowest Queries ===')
            ->expectsOutput('Query Time: 2.50s | Rows Examined: 10000 | Rows Sent: 100')
            ->expectsOutput('Lock Time: 0.00s | Database: test_db')
            ->expectsOutput("Query: SELECT * FROM users WHERE name LIKE '%test%';")
            ->expectsOutput('=== Query Pattern Analysis ===')
            ->expectsOutput('=== Lock Time Analysis ===')
            ->expectsOutput('=== Recommendations ===')
            ->expectsOutput('Analysis completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new AnalyzeSlowQueries();
        $this->assertEquals('queries:analyze-slow', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new AnalyzeSlowQueries();
        $this->assertEquals('Analyze slow query logs and provide optimization recommendations', $command->getDescription());
    }
}
