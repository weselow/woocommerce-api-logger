<?php
/**
 * Plugin Name: WooCommerce API Logger
 * Description: Логирует входящие запросы и ответы WooCommerce REST API (v3).
 * Version: 1.2.1
 * Author: Viking01
 */

if (!defined('ABSPATH')) exit;

load_plugin_textdomain('woocommerce-api-logger', false, dirname(plugin_basename(__FILE__)) . '/languages');

define('WOO_API_LOGGER_DIR', plugin_dir_path(__FILE__));
define('WOO_API_LOGGER_URL', plugin_dir_url(__FILE__));
define('WOO_API_LOGGER_LOG_DIR', wp_upload_dir()['basedir'] . '/wc-api-logs');
define('WOO_API_LOGGER_RETENTION_DAYS', 30);

require_once WOO_API_LOGGER_DIR . 'includes/LoggerService.php';
require_once WOO_API_LOGGER_DIR . 'includes/LoggerAdminPage.php';
require_once WOO_API_LOGGER_DIR . 'includes/LoggerCleaner.php';
require_once WOO_API_LOGGER_DIR . 'includes/LoggerSettingsPage.php';

add_action('rest_pre_dispatch', ['LoggerService', 'capture_request'], 10, 3);
add_filter('rest_post_dispatch', ['LoggerService', 'capture_response'], 10, 3);

add_action('admin_menu', function () {
    add_menu_page('Woo API Logs', 'Woo API Logs', 'manage_woocommerce', 'woo-api-logs', ['LoggerAdminPage', 'render'], 'dashicons-media-text');
});
add_action('admin_menu', ['LoggerSettingsPage', 'add_settings_page']);

add_action('init', ['LoggerCleaner', 'cleanup_old_logs']);

add_action('wp_ajax_woo_api_logger_load_json', ['LoggerAdminPage', 'ajax_load_json']);
add_action('wp_ajax_woo_api_logger_delete_log', ['LoggerAdminPage', 'ajax_delete_log']);
add_action('wp_ajax_woo_api_logger_list_dirs', ['LoggerAdminPage', 'ajax_list_dirs']);

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'toplevel_page_woo-api-logs') {
        wp_enqueue_script('woo-api-logger-admin', WOO_API_LOGGER_URL . 'assets/admin.js', ['jquery'], null, true);
        wp_localize_script('woo-api-logger-admin', 'WooApiLoggerAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('woo_api_logger_nonce'),
            'refresh_interval' => 30,
        ]);
    }
});
