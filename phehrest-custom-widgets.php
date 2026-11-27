<?php
/**
 * Plugin Name: Phehrest Custom Widgets
 * Description: Independent, opt-in Elementor widgets.
 * Version: 0.2.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Text Domain: phehrest-custom-widgets
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) { exit; }

define('PCW_FILE', __FILE__);
define('PCW_PATH', plugin_dir_path(__FILE__));
define('PCW_URL', plugin_dir_url(__FILE__));
define('PCW_VERSION', '0.2.0');

spl_autoload_register(static function ($class) {
    $prefix = 'Phehrest\\CustomWidgets\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) { return; }
    $relative = substr($class, strlen($prefix));
    $path = PCW_PATH . 'src/' . str_replace('\\', '/', $relative) . '.php';
    foreach (glob(PCW_PATH . 'widgets/*/manifest.json') ?: [] as $manifestFile) {
        $manifest = json_decode((string) file_get_contents($manifestFile), true);
        $widgetPrefix = is_array($manifest) ? ($manifest['autoload_namespace'] ?? '') : '';
        if ($widgetPrefix && strncmp($class, $widgetPrefix, strlen($widgetPrefix)) === 0) {
            $path = dirname($manifestFile) . '/src/' . str_replace('\\', '/', substr($class, strlen($widgetPrefix))) . '.php';
            break;
        }
    }
    if (is_file($path)) { require $path; }
});

add_action('plugins_loaded', static function () {
    load_plugin_textdomain('phehrest-custom-widgets', false, dirname(plugin_basename(PCW_FILE)) . '/languages');
    (new \Phehrest\CustomWidgets\Plugin())->boot();
});
