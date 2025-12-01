<?php
namespace SenhengCore\App\Services\Import;

class Logger
{
    /**
     * Log info message
     * 
     * @param string $file  Log file path (kept for compatibility, not used)
     * @param string $msg   Message to log
     */
    public static function info(string $file, string $msg): void
    {
        ProgressStore::addLog('INFO', $msg);
        // Also write to file for backup/debugging
        self::write($file, 'INFO', $msg);
    }

    /**
     * Log error message
     * 
     * @param string $file  Log file path (kept for compatibility, not used)
     * @param string $msg   Message to log
     */
    public static function error(string $file, string $msg): void
    {
        ProgressStore::addLog('ERROR', $msg);
        // Also write to file for backup/debugging
        self::write($file, 'ERROR', $msg);
    }

    /**
     * Log warning message
     * 
     * @param string $file  Log file path (kept for compatibility, not used)
     * @param string $msg   Message to log
     */
    public static function warning(string $file, string $msg): void
    {
        ProgressStore::addLog('WARNING', $msg);
        // Also write to file for backup/debugging
        self::write($file, 'WARNING', $msg);
    }

    /**
     * Write log entry to file
     * 
     * @param string $file   Log file path
     * @param string $level  Log level
     * @param string $msg    Message
     */
    private static function write(string $file, string $level, string $msg): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $entry = sprintf("[%s] %s: %s\n", $timestamp, $level, $msg);
        @file_put_contents($file, $entry, FILE_APPEND);
    }

    /**
     * Clear log file
     * 
     * @param string $file  Log file path
     */
    public static function clear(string $file): void
    {
        @file_put_contents($file, '');
    }
}

