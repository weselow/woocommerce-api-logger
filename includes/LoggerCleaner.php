<?php

class LoggerCleaner
{
    public static function cleanup_old_logs()
    {
        $logDir = WOO_API_LOGGER_LOG_DIR;

        if (!file_exists($logDir)) return;

        $retentionDays = defined('WOO_API_LOGGER_RETENTION_DAYS') ? WOO_API_LOGGER_RETENTION_DAYS : 30;
        $now = time();

        foreach (glob($logDir . '/*') as $folder) {
            if (!is_dir($folder)) continue;

            $modified = filemtime($folder);
            $ageDays = ($now - $modified) / 86400;

            if ($ageDays > $retentionDays) {
                foreach (glob($folder . '/*') as $file) {
                    @unlink($file);
                }
                @rmdir($folder);
            }
        }
    }
}
