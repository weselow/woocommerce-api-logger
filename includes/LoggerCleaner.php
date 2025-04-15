<?php

class LoggerCleaner
{
    public static function cleanup_old_logs()
    {
        $logDir = WOO_API_LOGGER_LOG_DIR;

        if (!file_exists($logDir)) return;

        $retentionDays = get_option('woo_api_logger_retention_days', 30);
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