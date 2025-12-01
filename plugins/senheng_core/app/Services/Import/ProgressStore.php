<?php
namespace SenhengCore\App\Services\Import;

class ProgressStore
{
    private static array $state = [
        'last_pointer'      => null,
        'last_run'          => null,
        'total_processed'   => 0,
        'total_created'     => 0,
        'total_updated'     => 0,
        'total_variations'  => 0,
        'total_errors'      => 0,
        'total_skipped'     => 0,
        'log_messages'      => [],
    ];

    /**
     * Load progress state from database
     * 
     * @return array
     */
    public static function get(): array
    {
        // Always load from database to ensure we have the latest cumulative progress
        // Each AJAX request is a new PHP process, so static state is reset
        
        // Clear cache first to ensure we get the latest data
        wp_cache_delete('sh_import_progress', 'options');
        
        $raw = get_option('sh_import_progress');
        if ($raw && is_array($raw)) {
            // Merge with defaults to ensure all keys exist
            self::$state = array_merge(self::$state, $raw);
            
            // Debug logging to track what we loaded from database
            error_log('[ProgressStore] Loaded from DB: ' . json_encode([
                'processed' => self::$state['total_processed'] ?? 0,
                'created' => self::$state['total_created'] ?? 0,
                'updated' => self::$state['total_updated'] ?? 0,
                'variations' => self::$state['total_variations'] ?? 0,
                'skipped' => self::$state['total_skipped'] ?? 0,
                'errors' => self::$state['total_errors'] ?? 0
            ]));
        } else {
            // No saved state found, use defaults
            error_log('[ProgressStore] No saved state found, using defaults');
        }
        
        return self::$state;
    }

    /**
     * Update pointer after processing an item
     * 
     * @param array $item  Feed row data
     */
    public static function tick(array $item): void
    {
        self::$state['last_pointer'] = self::pointerFrom($item);
        self::$state['last_run']     = current_time('mysql');
        self::$state['total_processed']++;
    }

    /**
     * Increment a counter
     * 
     * @param string $key  Counter key (e.g., 'total_created', 'total_updated')
     */
    public static function inc(string $key): void
    {
        if (!isset(self::$state[$key])) {
            self::$state[$key] = 0;
        }
        self::$state[$key]++;
    }

    /**
     * Save progress state to database
     */
    public static function save(): void
    {
        // CRITICAL: Force immediate save to database with autoload disabled for performance
        $result = update_option('sh_import_progress', self::$state, false);
        
        // Debug logging to track save operations and verify database write
        error_log('[ProgressStore] Save result: ' . ($result ? 'SUCCESS' : 'FAILED') . ' - State: ' . json_encode([
            'processed' => self::$state['total_processed'] ?? 0,
            'created' => self::$state['total_created'] ?? 0,
            'updated' => self::$state['total_updated'] ?? 0,
            'variations' => self::$state['total_variations'] ?? 0,
            'skipped' => self::$state['total_skipped'] ?? 0,
            'errors' => self::$state['total_errors'] ?? 0
        ]));
        
        // Force WordPress to clear any option cache to ensure fresh reads
        wp_cache_delete('sh_import_progress', 'options');
    }

    /**
     * Reset progress (for starting fresh)
     */
    public static function reset(): void
    {
        self::$state = [
            'last_pointer'      => null,
            'last_run'          => current_time('mysql'),
            'total_processed'   => 0,
            'total_created'     => 0,
            'total_updated'     => 0,
            'total_variations'  => 0,
            'total_errors'      => 0,
            'total_skipped'     => 0,
            'log_messages'      => [],
        ];
        self::save();
    }

    /**
     * Get current statistics
     * 
     * @return array
     */
    public static function getStats(): array
    {
        // Return current in-memory state without reloading from database
        // This ensures we get the most up-to-date cumulative statistics
        return [
            'processed'   => self::$state['total_processed'] ?? 0,
            'created'     => self::$state['total_created'] ?? 0,
            'updated'     => self::$state['total_updated'] ?? 0,
            'variations'  => self::$state['total_variations'] ?? 0,
            'skipped'     => self::$state['total_skipped'] ?? 0,
            'errors'      => self::$state['total_errors'] ?? 0,
        ];
    }

    /**
     * Add log message to progress store
     * 
     * @param string $level   Log level (INFO, ERROR, WARNING)
     * @param string $message Log message
     */
    public static function addLog(string $level, string $message): void
    {
        $timestamp = current_time('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'level'     => $level,
            'message'   => $message
        ];
        
        self::$state['log_messages'][] = $logEntry;
        
        // Keep only last 1000 log messages to prevent memory issues
        if (count(self::$state['log_messages']) > 1000) {
            self::$state['log_messages'] = array_slice(self::$state['log_messages'], -1000);
        }
    }

    /**
     * Get log messages
     * 
     * @return array
     */
    public static function getLogs(): array
    {
        return self::$state['log_messages'] ?? [];
    }

    /**
     * Clear log messages
     */
    public static function clearLogs(): void
    {
        self::$state['log_messages'] = [];
    }

    /**
     * Generate unique pointer from item data
     * 
     * @param array $r  Feed row
     * @return string
     */
    private static function pointerFrom(array $r): string
    {
        $title = trim((string)($r['Product_Name'] ?? ''));
        $sku   = trim((string)($r['sku_code'] ?? ''));
        return md5(strtolower($title . '|' . $sku));
    }
}

