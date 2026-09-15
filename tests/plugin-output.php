<?php
// Check plugin bootstrap and activation without touching the site's database or schedules.
if (PHP_SAPI !== 'cli') exit;
define('ABSPATH', dirname(__DIR__, 4) . '/');
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'https://example.test/plugins/stock-vest/'; }
function plugin_basename($file) { return basename($file); }
function add_action(...$args) {}
function add_filter(...$args) {}
function add_shortcode(...$args) {}
function register_activation_hook(...$args) {}
function register_deactivation_hook(...$args) {}
function get_option($key, $default = false) {
    if ($key === 'wsi_db_version') return '1.0.7';
    if ($key === 'wsi_options') return [];
    return $default;
}
function update_option(...$args) {}
function wp_next_scheduled($hook) { return false; }
function wp_schedule_event(...$args) {}

ob_start();
require dirname(__DIR__) . '/stock-vest.php';
$bootstrap = ob_get_clean();
ob_start();
wsi_activate();
$activation = ob_get_clean();
if ($bootstrap !== '' || $activation !== '') {
    fwrite(STDERR, 'Unexpected output: bootstrap=' . strlen($bootstrap) . ', activation=' . strlen($activation) . PHP_EOL);
    exit(1);
}
echo "PASS: plugin bootstrap and activation emit zero bytes (WordPress services stubbed).\n";
