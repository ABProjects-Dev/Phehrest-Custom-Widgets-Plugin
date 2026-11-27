<?php
namespace Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart;
define('ABSPATH', __DIR__ . '/');
$GLOBALS['pcw_options'] = [];
function wp_salt($scheme) { return 'test-secret'; }
function wp_cache_delete(...$args) {}
function get_option($name, $default = false) { return $GLOBALS['pcw_options'][$name] ?? $default; }
function update_option($name, $value, $autoload = null) { $GLOBALS['pcw_options'][$name] = $value; return true; }
function __($text, $domain = '') { return $text; }
final class MutationGuard {
    static function lock_name($name) { return $name; }
    static function lock($name, $seconds) { return true; }
    static function unlock($name) {}
    static function customer() { return 'guest:test-session'; }
}
require dirname(__DIR__) . '/widgets/ajax-smart-add-to-cart/src/CartException.php';
require dirname(__DIR__) . '/widgets/ajax-smart-add-to-cart/src/IdempotencyStore.php';
$store = new IdempotencyStore(); $id = str_repeat('a', 32);
if ($store->reserve($id, 'payload') !== null) throw new \RuntimeException('new operation not reserved');
try { $store->reserve($id, 'payload'); throw new \RuntimeException('pending replay was not rejected'); } catch (CartException $e) {}
try { $store->reserve($id, 'changed'); throw new \RuntimeException('mismatched replay was not rejected'); } catch (CartException $e) {}
$store->complete($id, ['key' => 'cart-key', 'quantity' => 1]);
if ($store->reserve($id, 'payload') !== ['key' => 'cart-key', 'quantity' => 1]) throw new \RuntimeException('completed replay not returned');
try { $store->reserve('unsafe', 'payload'); throw new \RuntimeException('invalid ID was accepted'); } catch (CartException $e) {}
echo "PASS: request reservation, pending protection, signature and replay\n";
