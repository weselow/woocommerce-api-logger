<?php

class LoggerService
{
    private static string $currentLogDir = '';
    private static float $requestStart = 0.0;

    public static function capture_request($result, $server, $request)
    {
        if (strpos($request->get_route(), '/wc/v3/') === false) {
            return $result;
        }

        self::$requestStart = microtime(true);

        $route = $request->get_route();
        $method = $request->get_method();
        $timestamp = gmdate('Y-m-d\TH_i_s\Z');
        $slug = trim(str_replace(['/', '\\'], ['-', '-'], $route), '-');
        $dirName = "{$timestamp}___{$method}__{$slug}";

        $logDir = trailingslashit(WOO_API_LOGGER_LOG_DIR) . $dirName;

        if (!file_exists($logDir)) {
            wp_mkdir_p($logDir);
        }

        self::$currentLogDir = $logDir;

        $requestData = [
            'timestamp' => $timestamp,
            'method' => $method,
            'route' => $route,
            'params' => $request->get_params(),
            'body' => $request->get_json_params(),
            'headers' => $request->get_headers(),
        ];

        file_put_contents("{$logDir}/request.json", json_encode($requestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $result;
    }

    public static function capture_response($response, $server, $request)
    {
        if (empty(self::$currentLogDir)) return $response;

        $duration = round(microtime(true) - self::$requestStart, 4);

        $responseData = [
            'status' => $response->get_status(),
            'duration_sec' => $duration,
            'data' => $response->get_data(),
        ];

        file_put_contents(self::$currentLogDir . '/response.json', json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response;
    }
}
