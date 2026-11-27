<?php
/** Small contract test using WordPress stubs; not a complete non-invasive integration test. */
define('ABSPATH', __DIR__ . '/');
define('PCW_PATH', dirname(__DIR__) . '/');
define('ELEMENTOR_VERSION', '3.18.0');
define('WC_VERSION', '8.5.0');
class WooCommerce {}
function get_option($name, $default = false) { return $default; }
function get_bloginfo($name) { return '6.4'; }
function add_action(...$args) { $GLOBALS['hooks'][] = $args; }
function delete_option($name) { $GLOBALS['deleted'][] = $name; }
require PCW_PATH . 'src/Widgets/Registry.php';
$registry = new \Phehrest\CustomWidgets\Widgets\Registry();
if (count($registry->manifests()) !== 1 || $registry->enabled('phehrest_ajax_smart_add_to_cart')) { throw new RuntimeException('Widgets must default to disabled.'); }
$registry->boot();
if (!empty($GLOBALS['hooks'])) { throw new RuntimeException('Disabled widgets must not register hooks.'); }
define('PCW_URL', 'https://example.test/plugin/');
define('PCW_VERSION', 'test');
function wp_enqueue_style(...$args) { $GLOBALS['styles'][] = $args; }
function wp_enqueue_script(...$args) { $GLOBALS['scripts'][] = $args; }
require PCW_PATH . 'src/Admin/Dashboard.php';
$dashboard = new \Phehrest\CustomWidgets\Admin\Dashboard($registry);
$dashboard->boot();
$names = array_map(static function ($hook) { return $hook[0]; }, $GLOBALS['hooks']);
if (in_array('admin_post_pcw_save_design', $names, true) || !in_array('admin_post_pcw_save_widgets', $names, true)) { throw new RuntimeException('Unexpected admin save hooks'); }
$dashboard->assets('other_page');
if (!empty($GLOBALS['styles']) || !empty($GLOBALS['scripts'])) { throw new RuntimeException('Admin assets must be scoped to plugin page'); }
$dashboard->assets('toplevel_page_pcw-widgets');
if (count($GLOBALS['styles'] ?? []) !== 1 || count($GLOBALS['scripts'] ?? []) !== 1) { throw new RuntimeException('Dashboard assets not registered'); }
define('WP_UNINSTALL_PLUGIN', true);
require PCW_PATH . 'uninstall.php';
if (($GLOBALS['deleted'] ?? []) !== ['pcw_enabled_widgets', 'pcw_design_system', 'pcw_idempotency']) { throw new RuntimeException('Uninstall touched unexpected options.'); }
echo "PASS: default disabled, admin hooks/assets scoped, uninstall option scope\n";
