<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetupMySQLPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql:setup-performance {--force : Force setup even if already configured}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup MySQL performance monitoring based on Oh Dear\'s optimization guide';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up MySQL performance monitoring...');

        try {
            // Check if we can connect to MySQL
            DB::connection()->getPdo();
            $this->info('✓ MySQL connection successful');
        } catch (\Exception $e) {
            $this->error('✗ Cannot connect to MySQL: ' . $e->getMessage());
            return 1;
        }

        $config = config('mysql-performance');

        // Setup slow query log
        if ($config['slow_query_log']['enabled']) {
            $this->setupSlowQueryLog($config['slow_query_log']);
        }

        // Setup general query log
        if ($config['general_query_log']['enabled']) {
            $this->setupGeneralQueryLog($config['general_query_log']);
        }

        // Setup performance schema
        if ($config['performance_schema']['enabled']) {
            $this->setupPerformanceSchema();
        }

        // Setup query optimization
        $this->setupQueryOptimization($config['query_optimization']);

        // Setup connection optimization
        $this->setupConnectionOptimization($config['connection_optimization']);

        // Setup buffer optimization
        $this->setupBufferOptimization($config['buffer_optimization']);

        $this->info('✓ MySQL performance monitoring setup completed');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Monitor slow query log: tail -f ' . $config['slow_query_log']['file']);
        $this->line('2. Analyze queries with pt-query-digest (if installed)');
        $this->line('3. Check Laravel Debug Bar for local development');
        $this->line('4. Monitor query performance in application logs');

        return 0;
    }

    /**
     * Setup slow query log
     */
    private function setupSlowQueryLog(array $config): void
    {
        try {
            DB::statement("SET GLOBAL slow_query_log_file = '{$config['file']}'");
            DB::statement("SET GLOBAL long_query_time = {$config['long_query_time']}");
            DB::statement("SET GLOBAL slow_query_log = 'ON'");
            DB::statement("SET GLOBAL log_queries_not_using_indexes = " . ($config['log_queries_not_using_indexes'] ? 'ON' : 'OFF'));
            DB::statement("SET GLOBAL min_examined_row_limit = {$config['min_examined_row_limit']}");

            $this->info('✓ Slow query log configured');
        } catch (\Exception $e) {
            $this->warn('⚠ Could not configure slow query log: ' . $e->getMessage());
        }
    }

    /**
     * Setup general query log
     */
    private function setupGeneralQueryLog(array $config): void
    {
        try {
            DB::statement("SET GLOBAL general_log_file = '{$config['file']}'");
            DB::statement("SET GLOBAL general_log = 'ON'");

            $this->info('✓ General query log configured');
        } catch (\Exception $e) {
            $this->warn('⚠ Could not configure general query log: ' . $e->getMessage());
        }
    }

    /**
     * Setup performance schema
     */
    private function setupPerformanceSchema(): void
    {
        try {
            DB::statement("SET GLOBAL performance_schema = 'ON'");
            DB::statement("UPDATE performance_schema.setup_instruments SET ENABLED = 'YES' WHERE NAME LIKE '%statement%'");
            DB::statement("UPDATE performance_schema.setup_consumers SET ENABLED = 'YES' WHERE NAME LIKE '%events_statements%'");

            $this->info('✓ Performance schema configured');
        } catch (\Exception $e) {
            $this->warn('⚠ Could not configure performance schema: ' . $e->getMessage());
        }
    }

    /**
     * Setup query optimization
     */
    private function setupQueryOptimization(array $config): void
    {
        try {
            if ($config['enable_query_cache']) {
                DB::statement("SET GLOBAL query_cache_size = '{$config['query_cache_size']}'");
                DB::statement("SET GLOBAL query_cache_type = '{$config['query_cache_type']}'");
                DB::statement("SET GLOBAL query_cache_limit = '{$config['query_cache_limit']}'");
            }

            $this->info('✓ Query optimization configured');
        } catch (\Exception $e) {
            $this->warn('⚠ Could not configure query optimization: ' . $e->getMessage());
        }
    }

    /**
     * Setup connection optimization
     */
    private function setupConnectionOptimization(array $config): void
    {
        try {
            DB::statement("SET GLOBAL max_connections = {$config['max_connections']}");
            DB::statement("SET GLOBAL connect_timeout = {$config['connect_timeout']}");
            DB::statement("SET GLOBAL wait_timeout = {$config['wait_timeout']}");
            DB::statement("SET GLOBAL interactive_timeout = {$config['interactive_timeout']}");

            $this->info('✓ Connection optimization configured');
        } catch (\Exception $e) {
            $this->warn('⚠ Could not configure connection optimization: ' . $e->getMessage());
        }
    }

    /**
     * Setup buffer optimization
     */
    private function setupBufferOptimization(array $config): void
    {
        try {
            DB::statement("SET GLOBAL innodb_buffer_pool_size = '{$config['innodb_buffer_pool_size']}'");
            DB::statement("SET GLOBAL innodb_log_file_size = '{$config['innodb_log_file_size']}'");
            DB::statement("SET GLOBAL innodb_log_buffer_size = '{$config['innodb_log_buffer_size']}'");
            DB::statement("SET GLOBAL key_buffer_size = '{$config['key_buffer_size']}'");

            $this->info('✓ Buffer optimization configured');
        } catch (\Exception $e) {
            $this->warn('⚠ Could not configure buffer optimization: ' . $e->getMessage());
        }
    }
}
