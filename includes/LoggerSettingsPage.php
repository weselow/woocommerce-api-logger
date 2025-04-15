<?php

class LoggerSettingsPage
{
    public static function add_settings_page()
    {
        add_submenu_page(
            'woo-api-logs',
            'Настройки логгера',
            'Настройки',
            'manage_woocommerce',
            'woo-api-logs-settings',
            [self::class, 'render']
        );
    }

    public static function render()
    {
        if (!current_user_can('manage_woocommerce')) return;

        if (isset($_POST['woo_api_logger_save'])) {
            check_admin_referer('woo_api_logger_settings');

            update_option('woo_api_logger_enabled', isset($_POST['woo_api_logger_enabled']) ? '1' : '0');
            update_option('woo_api_logger_retention_days', absint($_POST['woo_api_logger_retention_days']));
            update_option('woo_api_logger_refresh_interval', absint($_POST['woo_api_logger_refresh_interval']));
        }

        $enabled = get_option('woo_api_logger_enabled', '1');
        $retention = get_option('woo_api_logger_retention_days', 30);
        $refresh = get_option('woo_api_logger_refresh_interval', 30);

        echo '<div class="wrap"><h1>Настройки логгера Woo API</h1>';
        echo '<form method="post">';
        wp_nonce_field('woo_api_logger_settings');
        echo '<table class="form-table">';
        echo '<tr><th scope="row">Включить логирование</th><td><input type="checkbox" name="woo_api_logger_enabled" value="1" ' . checked($enabled, '1', false) . '></td></tr>';
        echo '<tr><th scope="row">Срок хранения (дней)</th><td><input type="number" name="woo_api_logger_retention_days" value="' . esc_attr($retention) . '" min="1"></td></tr>';
        echo '<tr><th scope="row">Интервал автообновления (сек)</th><td><input type="number" name="woo_api_logger_refresh_interval" value="' . esc_attr($refresh) . '" min="5"></td></tr>';
        echo '</table>';
        echo '<p><input type="submit" name="woo_api_logger_save" class="button button-primary" value="Сохранить изменения"></p>';
        echo '</form></div>';
    }
}