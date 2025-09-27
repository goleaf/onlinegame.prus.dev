<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AnalyzeSlowQueries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql:analyze-slow-queries 
                            {--file= : Path to slow query log file}
                            {--limit=25 : Number of queries to analyze}
                            {--format=table : Output format (table, json, csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze MySQL slow query log and provide optimization recommendations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logFile = $this->option('file') ?? config('mysql-performance.slow_query_log.file');
        $limit = (int) $this->option('limit');
        $format = $this->option('format');

        if (!File::exists($logFile)) {
            $this->error("Slow query log file not found: {$logFile}");
            $this->info('Make sure slow query log is enabled and the file exists.');
            return 1;
        }

        $this->info("Analyzing slow queries from: {$logFile}");
        $this->newLine();

        $queries = $this->parseSlowQueryLog($logFile);
        $analyzedQueries = $this->analyzeQueries($queries, $limit);

        $this->displayResults($analyzedQueries, $format);

        return 0;
    }

    /**
     * Parse slow query log file
     */
    private function parseSlowQueryLog(string $logFile): array
    {
        $content = File::get($logFile);
        $queries = [];
        $currentQuery = null;

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Check if this is a query time line
            if (preg_match('/^# Query_time: ([\d.]+)\s+Lock_time: ([\d.]+)\s+Rows_sent: (\d+)\s+Rows_examined: (\d+)/', $line, $matches)) {
                if ($currentQuery) {
                    $queries[] = $currentQuery;
                }

                $currentQuery = [
                    'query_time' => (float) $matches[1],
                    'lock_time' => (float) $matches[2],
                    'rows_sent' => (int) $matches[3],
                    'rows_examined' => (int) $matches[4],
                    'sql' => '',
                    'timestamp' => null,
                ];
            }
            // Check if this is a timestamp line
            elseif (preg_match('/^SET timestamp=(\d+);/', $line, $matches)) {
                if ($currentQuery) {
                    $currentQuery['timestamp'] = (int) $matches[1];
                }
            }
            // This is the SQL query
            elseif ($currentQuery && !str_starts_with($line, '#')) {
                $currentQuery['sql'] .= $line . ' ';
            }
        }

        // Add the last query
        if ($currentQuery) {
            $queries[] = $currentQuery;
        }

        return $queries;
    }

    /**
     * Analyze queries and provide recommendations
     */
    private function analyzeQueries(array $queries, int $limit): array
    {
        // Sort by query time (slowest first)
        usort($queries, fn($a, $b) => $b['query_time'] <=> $a['query_time']);

        $analyzed = array_slice($queries, 0, $limit);

        foreach ($analyzed as &$query) {
            $query['recommendations'] = $this->generateRecommendations($query);
            $query['fingerprint'] = $this->generateFingerprint($query['sql']);
        }

        return $analyzed;
    }

    /**
     * Generate optimization recommendations
     */
    private function generateRecommendations(array $query): array
    {
        $recommendations = [];
        $sql = strtolower($query['sql']);

        // Check for missing indexes
        if ($query['rows_examined'] > $query['rows_sent'] * 10) {
            $recommendations[] = 'Consider adding indexes - examining ' . $query['rows_examined'] . ' rows but only returning ' . $query['rows_sent'];
        }

        // Check for SELECT *
        if (str_contains($sql, 'select *')) {
            $recommendations[] = 'Avoid SELECT * - specify only needed columns';
        }

        // Check for ORDER BY without LIMIT
        if (preg_match('/order by.*(?!limit)/i', $sql) && !str_contains($sql, 'limit')) {
            $recommendations[] = 'Consider adding LIMIT to ORDER BY queries';
        }

        // Check for LIKE with leading wildcard
        if (preg_match('/like\s+[\'"]%/', $sql)) {
            $recommendations[] = 'Avoid leading wildcards in LIKE queries - they prevent index usage';
        }

        // Check for functions in WHERE clause
        if (preg_match('/where.*\w+\(/i', $sql)) {
            $recommendations[] = 'Avoid functions in WHERE clause - they prevent index usage';
        }

        // Check for N+1 potential
        if (str_contains($sql, 'where id =') && $query['query_time'] < 0.1) {
            $recommendations[] = 'Potential N+1 query - consider eager loading';
        }

        return $recommendations;
    }

    /**
     * Generate query fingerprint for grouping similar queries
     */
    private function generateFingerprint(string $sql): string
    {
        // Remove specific values and normalize whitespace
        $fingerprint = preg_replace('/\d+/', '?', $sql);
        $fingerprint = preg_replace('/[\'"][^\'"]*[\'"]/', '?', $fingerprint);
        $fingerprint = preg_replace('/\s+/', ' ', $fingerprint);

        return trim($fingerprint);
    }

    /**
     * Display analysis results
     */
    private function displayResults(array $queries, string $format): void
    {
        if (empty($queries)) {
            $this->info('No slow queries found in the log file.');
            return;
        }

        switch ($format) {
            case 'json':
                $this->line(json_encode($queries, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $this->displayCsv($queries);
                break;
            default:
                $this->displayTable($queries);
        }
    }

    /**
     * Display results in table format
     */
    private function displayTable(array $queries): void
    {
        $headers = ['Query Time (s)', 'Rows Examined', 'Rows Sent', 'Recommendations'];
        $rows = [];

        foreach ($queries as $query) {
            $rows[] = [
                number_format($query['query_time'], 3),
                number_format($query['rows_examined']),
                number_format($query['rows_sent']),
                implode('; ', $query['recommendations']) ?: 'None'
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('Top slow queries:');

        foreach (array_slice($queries, 0, 5) as $index => $query) {
            $this->line(($index + 1) . '. ' . substr($query['sql'], 0, 100) . '...');
            if (!empty($query['recommendations'])) {
                foreach ($query['recommendations'] as $recommendation) {
                    $this->line("   • {$recommendation}");
                }
            }
            $this->newLine();
        }
    }

    /**
     * Display results in CSV format
     */
    private function displayCsv(array $queries): void
    {
        $this->line('Query Time,Rows Examined,Rows Sent,SQL,Recommendations');

        foreach ($queries as $query) {
            $recommendations = implode('; ', $query['recommendations']);
            $sql = str_replace(["\n", "\r"], ' ', $query['sql']);
            $sql = str_replace('"', '""', $sql);

            $this->line(sprintf(
                '%.3f,%d,%d,"%s","%s"',
                $query['query_time'],
                $query['rows_examined'],
                $query['rows_sent'],
                $sql,
                $recommendations
            ));
        }
    }
}

