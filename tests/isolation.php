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
define('WP_UNINSTALL_PLUGIN', true);
require PCW_PATH . 'uninstall.php';
if (($GLOBALS['deleted'] ?? []) !== ['pcw_enabled_widgets']) { throw new RuntimeException('Uninstall touched unexpected options.'); }
echo "PASS: default disabled, no module hooks, uninstall option scope\n";
