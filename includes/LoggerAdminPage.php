<?php
class LoggerAdminPage
{
    public static function render()
    {
        $baseDir = WOO_API_LOGGER_LOG_DIR;
        if (!file_exists($baseDir)) wp_mkdir_p($baseDir);
        $folders = array_filter(glob($baseDir . '/*'), 'is_dir');
        usort($folders, fn($a, $b) => filemtime($b) <=> filemtime($a));
        $folders = array_slice($folders, 0, 50);

        echo '<div class="wrap"><h1>Woo API Logs</h1>';
        echo '<div style="margin-bottom:10px;"><strong>Легенда:</strong> ';
        echo '<span title="Создание">🆕</span> — создание, ';
        echo '<span title="Обновление">✏️</span> — обновление, ';
        echo '<span title="Удаление">❌</span> — удаление</div>';

        echo '<table class="widefat fixed striped woocommerce-api-log-table">';
        echo '<thead><tr>
                <th>Время</th><th>Метод</th><th>Маршрут</th><th>Длительность</th>
                <th>Request</th><th>Response</th><th>Удалить</th>
              </tr></thead><tbody>';

        foreach ($folders as $folder) {
            $request = @json_decode(file_get_contents($folder . '/request.json'), true);
            $response = @json_decode(file_get_contents($folder . '/response.json'), true);
            if (!$request) continue;

            $ts = $request['timestamp'] ?? '';
            $date = DateTime::createFromFormat('Y-m-d\TH_i_s\Z', $ts, new DateTimeZone('UTC'));
            $formatted = $date ? $date->setTimezone(wp_timezone())->format('d.m.Y H:i:s') : $ts;
            $method = $request['method'] ?? '❓';
            $route = $request['route'] ?? '❓';
            $duration = $response['duration_sec'] ?? '-';
            $folderName = basename($folder);

            $batchSummary = '';
            if ($route === '/wc/v3/products/batch' && isset($request['body'])) {
                $body = $request['body'];
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
            echo "<td>{$formatted}</td><td>{$method}</td><td>{$route}{$batchSummary}</td><td>{$duration} сек</td>";
            echo "<td><button class='woo-api-view-json' data-type='request' data-folder='{$folderName}'>🔍</button></td>";
            echo "<td><button class='woo-api-view-json' data-type='response' data-folder='{$folderName}'>🔍</button></td>";
            echo "<td><button class='woo-api-delete-log' data-folder='{$folderName}'>🗑</button></td>";
            echo '</tr>';
            echo "<tr class='woo-api-json-row' data-json='{$folderName}-request'><td colspan='7'><pre style='display:none' class='woo-api-json-content'></pre></td></tr>";
            echo "<tr class='woo-api-json-row' data-json='{$folderName}-response'><td colspan='7'><pre style='display:none' class='woo-api-json-content'></pre></td></tr>";
        }

        echo '</tbody></table></div>';
    }

    public static function ajax_list_dirs()
    {
        $baseDir = WOO_API_LOGGER_LOG_DIR;
        if (!file_exists($baseDir)) wp_send_json_success([]);

        $dirs = array_filter(glob($baseDir . '/*'), 'is_dir');
        usort($dirs, fn($a, $b) => filemtime($b) <=> filemtime($a));
        $dirs = array_slice($dirs, 0, 50);

        $result = [];

        foreach ($dirs as $dir) {
            $folder = basename($dir);
            $request = @json_decode(file_get_contents($dir . '/request.json'), true);
            $response = @json_decode(file_get_contents($dir . '/response.json'), true);
            if (!$request) continue;

            $counts = ['create' => 0, 'update' => 0, 'delete' => 0];
            if ($request['route'] === '/wc/v3/products/batch' && isset($request['body'])) {
                $body = $request['body'];
                $counts = [
                    'create' => is_array($body['create'] ?? null) ? count($body['create']) : 0,
                    'update' => is_array($body['update'] ?? null) ? count($body['update']) : 0,
                    'delete' => is_array($body['delete'] ?? null) ? count($body['delete']) : 0,
                ];
            }

            $result[] = [
                'folder' => $folder,
                'timestamp' => $request['timestamp'] ?? '',
                'method' => $request['method'] ?? '',
                'route' => $request['route'] ?? '',
                'duration' => $response['duration_sec'] ?? '-',
                'counts' => $counts
            ];
        }

        wp_send_json_success($result);
    }

    public static function ajax_load_json() {
        check_ajax_referer('woo_api_logger_nonce', 'nonce');
        $folder = basename($_POST['folder'] ?? '');
        $type = $_POST['type'] === 'response' ? 'response' : 'request';
        $file = WOO_API_LOGGER_LOG_DIR . '/' . $folder . "/{$type}.json";
        wp_send_json_success(file_get_contents($file));
    }

    public static function ajax_delete_log() {
        check_ajax_referer('woo_api_logger_nonce', 'nonce');
        $folder = basename($_POST['folder'] ?? '');
        $dir = WOO_API_LOGGER_LOG_DIR . '/' . $folder;
        foreach (glob($dir . '/*') as $file) @unlink($file);
        @rmdir($dir);
        wp_send_json_success('Удалено');
    }
}
