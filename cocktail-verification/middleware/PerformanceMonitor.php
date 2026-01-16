<?php
/**
 * Performance Monitoring & Query Optimization
 */
class PerformanceMonitor {
    private static $start_time;
    private static $queries = [];
    private static $memory_start;
    
    /**
     * Start monitoring
     */
    public static function start() {
        self::$start_time = microtime(true);
        self::$memory_start = memory_get_usage();
        
        // Enable slow query log
        if (function_exists('mysqli_report')) {
            mysqli_report(MYSQLI_REPORT_ALL);
        }
    }
    
    /**
     * Log query for analysis
     */
    public static function logQuery($sql, $duration) {
        if ($duration > 0.1) { // Log queries slower than 100ms
            self::$queries[] = [
                'sql' => $sql,
                'duration' => $duration,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            error_log("SLOW QUERY ({$duration}s): {$sql}");
        }
    }
    
    /**
     * Get performance report
     */
    public static function report() {
        $elapsed = microtime(true) - self::$start_time;
        $memory_used = memory_get_usage() - self::$memory_start;
        
        return [
            'execution_time' => round($elapsed, 3),
            'memory_used' => round($memory_used / 1024, 2), // KB
            'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2), // MB
            'slow_queries' => count(self::$queries),
            'queries' => self::$queries
        ];
    }
    
    /**
     * End monitoring and output report (debug only)
     */
    public static function end() {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $report = self::report();
            error_log(json_encode($report));
        }
    }
}

// Start monitoring on application init
PerformanceMonitor::start();
register_shutdown_function(['PerformanceMonitor', 'end']);
?>
