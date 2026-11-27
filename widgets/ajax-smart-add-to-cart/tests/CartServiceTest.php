<?php
/** Isolated behavioral tests: fake cart objects, not a substitute for WooCommerce integration. */
define('ABSPATH', __DIR__ . '/');
function __($message, $domain = '') { return $message; }
function wc_get_product($id) { return $GLOBALS['products'][$id] ?? null; }
function WC() { return $GLOBALS['wc']; }
function apply_filters($name, $value, ...$args) {
    if ($name === 'woocommerce_update_cart_validation' && !empty($GLOBALS['reject_update'])) return false;
    if ($name === 'woocommerce_add_to_cart_validation' && !empty($GLOBALS['reject_add'])) return false;
    if ($name === 'woocommerce_quantity_input_step') return $GLOBALS['step'] ?? $value;
    return $value;
}
function do_action(...$args) {}
class FakeProduct {
    public $stock = 10; public $individual = false; public $purchasable = true; public $backorder = false;
    function get_title() { return 'Test product'; } function get_status() { return 'publish'; } function is_visible() { return true; }
    function is_type($types) { return in_array('simple', (array) $types, true); }
    function get_min_purchase_quantity() { return 1; } function get_max_purchase_quantity() { return $this->individual ? 1 : -1; }
    function is_purchasable() { return $this->purchasable; } function is_in_stock() { return $this->stock > 0 || $this->backorder; }
    function is_sold_individually() { return $this->individual; } function get_stock_managed_by_id() { return 7; }
    function has_enough_stock($amount) { return $this->backorder || $amount <= $this->stock; }
}
class FakeCart {
    public $items = []; public $totals = 0;
    function get_cart() { return $this->items; }
    function get_cart_item_quantities() { $sum = 0; foreach ($this->items as $item) $sum += $item['quantity']; return [7 => $sum]; }
    function add_to_cart($id, $qty, $variation, $attributes) { $this->items['key'] = ['product_id' => $id, 'variation_id' => $variation, 'variation' => $attributes, 'quantity' => $qty]; $this->calculate_totals(); return 'key'; }
    function set_quantity($key, $qty, $refresh) { $this->items[$key]['quantity'] = $qty; if ($refresh) $this->calculate_totals(); return true; }
    function remove_cart_item($key) { unset($this->items[$key]); return true; }
    function calculate_totals() { $this->totals++; }
}
require dirname(__DIR__) . '/src/CartException.php';
require dirname(__DIR__) . '/src/CartService.php';
$service = new \Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart\CartService();
$GLOBALS['products'] = [7 => new FakeProduct()]; $GLOBALS['wc'] = (object) ['cart' => new FakeCart()];
function check($condition, $message) { if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } echo "PASS: $message\n"; }
function rejects($fn, $message) { try { $fn(); } catch (RuntimeException $e) { check(true, $message); return; } check(false, $message); }
$result = $service->change(7, 0, [], 'add', '', 0); check($result['quantity'] === 1, 'add zero to one');
$result = $service->change(7, 0, [], 'increase', 'key', 1); check($result['quantity'] === 2, 'increase one to two');
$result = $service->change(7, 0, [], 'increase', 'key', 2); check($result['quantity'] === 3, 'increase repeatedly');
$result = $service->change(7, 0, [], 'decrease', 'key', 3); check($result['quantity'] === 2, 'decrease three to two');
$result = $service->change(7, 0, [], 'decrease', 'key', 2); check($result['quantity'] === 1, 'decrease two to one');
rejects(fn() => $service->change(7, 0, [], 'increase', 'key', 2), 'stale request rejected');
$GLOBALS['reject_update'] = true; rejects(fn() => $service->change(7, 0, [], 'increase', 'key', 1), 'update validation respected'); $GLOBALS['reject_update'] = false;
$GLOBALS['step'] = 2; rejects(fn() => $service->change(7, 0, [], 'increase', 'key', 1), 'non-unit step rejected'); $GLOBALS['step'] = 1;
$GLOBALS['products'][7]->individual = true; rejects(fn() => $service->change(7, 0, [], 'increase', 'key', 1), 'sold individually respected'); $GLOBALS['products'][7]->individual = false;
$GLOBALS['products'][7]->stock = 1; rejects(fn() => $service->change(7, 0, [], 'increase', 'key', 1), 'stock limit respected');
$GLOBALS['products'][7]->backorder = true; $result = $service->change(7, 0, [], 'increase', 'key', 1); check($result['quantity'] === 2, 'backorder allowed');
$GLOBALS['wc']->cart->items['other'] = $GLOBALS['wc']->cart->items['key']; rejects(fn() => $service->find(7, 0, []), 'ambiguous cart rows rejected'); unset($GLOBALS['wc']->cart->items['other']);
$result = $service->change(7, 0, [], 'decrease', 'key', 2); $result = $service->change(7, 0, [], 'remove', 'key', 1); check($result['quantity'] === 0 && $result['key'] === '', 'remove one to zero');
check($GLOBALS['wc']->cart->totals >= 7, 'cart totals recalculated through cart API');
$GLOBALS['reject_add'] = true; rejects(fn() => $service->change(7, 0, [], 'add', '', 0), 'add validation respected');
