<?php

class LoggerAdminPage
{
    public static function render()
    {
        $baseDir = WOO_API_LOGGER_LOG_DIR;
        if (!file_exists($baseDir)) {
            wp_mkdir_p($baseDir);
        }

        $folders = array_filter(glob($baseDir . '/*'), 'is_dir');
        usort($folders, fn($a, $b) => filemtime($b) <=> filemtime($a));

        echo '<div class="wrap"><h1>Woo API Logs</h1>';

        // 👇 Легенда значков
        echo '<div style="margin-bottom:10px;"><strong>Легенда:</strong> ';
        echo '<span title="Создание">🆕</span> — создание, ';
        echo '<span title="Обновление">✏️</span> — обновление, ';
        echo '<span title="Удаление">❌</span> — удаление';
        echo '</div>';

        echo '<table class="widefat fixed striped woocommerce-api-log-table">';
        echo '<thead><tr>
            <th>Время</th><th>Метод</th><th>Маршрут</th><th>Длительность</th>
            <th>Request</th><th>Response</th><th>Удалить</th>
        </tr></thead><tbody>';

        foreach ($folders as $folder) {
            $requestPath = $folder . '/request.json';
            $responsePath = $folder . '/response.json';
            $folderName = basename($folder);

            $request = file_exists($requestPath) ? json_decode(file_get_contents($requestPath), true) : null;
            $response = file_exists($responsePath) ? json_decode(file_get_contents($responsePath), true) : null;

            if (!$request) continue;

            $ts = $request['timestamp'] ?? '';
            $date = DateTime::createFromFormat('Y-m-d\\TH_i_s\\Z', $ts, new DateTimeZone('UTC'));
            if ($date) {
                $date->setTimezone(wp_timezone());
                $formatted = $date->format('d.m.Y H:i:s');
            } else {
                $formatted = $ts;
            }

            $method = $request['method'] ?? '';
            $route = $request['route'] ?? '';
            $duration = $response['duration_sec'] ?? '-';

            // Подсчёт create/update/delete если маршрут batch
            $batchSummary = '';
            if ($route === '/wc/v3/products/batch') {
                $body = $request['body'] ?? [];
                $counts = [
                    'create' => is_array($body['create'] ?? null) ? count($body['create']) : 0,
                    'update' => is_array($body['update'] ?? null) ? count($body['update']) : 0,
                    'delete' => is_array($body['delete'] ?? null) ? count($body['delete']) : 0,
                ];
                $batchSummary = sprintf(
                    '<br><small>
                        <span title="Создание">🆕 %d</span>, 
                        <span title="Обновление">✏️ %d</span>, 
                        <span title="Удаление">❌ %d</span>
                    </small>',
                    $counts['create'], $counts['update'], $counts['delete']
                );
            }

            echo '<tr>';
            echo '<td>' . esc_html($formatted) . '</td>';
            echo '<td>' . esc_html($method) . '</td>';
            echo '<td>' . esc_html($route) . $batchSummary . '</td>';
            echo '<td>' . esc_html($duration) . ' сек</td>';
            echo '<td><button class="woo-api-view-json" data-type="request" data-folder="' . esc_attr($folderName) . '">🔍</button></td>';
            echo '<td><button class="woo-api-view-json" data-type="response" data-folder="' . esc_attr($folderName) . '">🔍</button></td>';
            echo '<td><button class="woo-api-delete-log" data-folder="' . esc_attr($folderName) . '">🗑</button></td>';
            echo '</tr>';
            echo '<tr class="woo-api-json-row" data-json="' . esc_attr($folderName) . '-request"><td colspan="7"><pre style="display:none" class="woo-api-json-content"></pre></td></tr>';
            echo '<tr class="woo-api-json-row" data-json="' . esc_attr($folderName) . '-response"><td colspan="7"><pre style="display:none" class="woo-api-json-content"></pre></td></tr>';
        }

        echo '</tbody></table></div>';
    }

    public static function ajax_load_json()
    {
        check_ajax_referer('woo_api_logger_nonce', 'nonce');

        $folder = basename($_POST['folder'] ?? '');
        $type = $_POST['type'] === 'response' ? 'response' : 'request';

        $file = WOO_API_LOGGER_LOG_DIR . '/' . $folder . "/{$type}.json";
        if (!file_exists($file)) {
            wp_send_json_error("Файл не найден.");
        }

        $content = file_get_contents($file);
        wp_send_json_success($content);
    }

    public static function ajax_delete_log()
    {
        check_ajax_referer('woo_api_logger_nonce', 'nonce');

        $folder = basename($_POST['folder'] ?? '');
        $dir = WOO_API_LOGGER_LOG_DIR . '/' . $folder;

        if (!is_dir($dir)) wp_send_json_error('Директория не найдена.');

        foreach (glob($dir . '/*') as $file) {
            @unlink($file);
        }
        @rmdir($dir);

        wp_send_json_success('Удалено');
    }

    public static function ajax_list_dirs()
    {
        check_ajax_referer('woo_api_logger_nonce', 'nonce');

        $baseDir = WOO_API_LOGGER_LOG_DIR;
        if (!file_exists($baseDir)) wp_send_json_success([]);

        $dirs = array_filter(glob($baseDir . '/*'), 'is_dir');
        $result = [];

        foreach ($dirs as $dir) {
            $folder = basename($dir);
            $request = @json_decode(file_get_contents($dir . '/request.json'), true);
            $response = @json_decode(file_get_contents($dir . '/response.json'), true);

            if (!$request) continue;

            $ts = $request['timestamp'] ?? '';
            $method = $request['method'] ?? '';
            $route = $request['route'] ?? '';
            $duration = $response['duration_sec'] ?? '-';

            $result[] = [
                'folder' => $folder,
                'timestamp' => $ts,
                'method' => $method,
                'route' => $route,
                'duration' => $duration,
            ];
        }

        wp_send_json_success($result);
    }
}
