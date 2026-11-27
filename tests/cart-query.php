<?php
/** Isolated contracts for the Elementor Pro cart source (Pro itself is not included). */
define('ABSPATH', __DIR__ . '/');
function __($text, $domain) { return $text; }
function add_action(...$args) { $GLOBALS['actions'][] = $args; }
function add_filter(...$args) { $GLOBALS['filters'][] = $args; }
function WC() { return $GLOBALS['wc'] ?? null; }
require dirname(__DIR__) . '/src/Query/CartProducts.php';
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } echo "PASS: $message\n"; }
class CartQueryWidget {
    public $name; public $settings; public $controls; public $updates = [];
    public function __construct($name, $settings = [], $controls = []) { $this->name = $name; $this->settings = $settings; $this->controls = $controls; }
    public function get_name() { return $this->name; }
    public function get_settings($id) { return $this->settings[$id] ?? null; }
    public function get_controls($id) { return $this->controls[$id] ?? null; }
    public function update_control($id, $options) { $this->updates[$id] = $options; }
}
class TestCart { public $items = []; public function get_cart() { return $this->items; } }
$query = new \Phehrest\CustomWidgets\Query\CartProducts(); $query->boot();
check(count($GLOBALS['actions']) === 3 && $GLOBALS['filters'][0][0] === 'elementor/query/query_args', 'scoped Elementor hook registration');
foreach (['posts' => 'posts_post_type', 'loop-grid' => 'post_query_post_type', 'loop-carousel' => 'post_query_post_type'] as $name => $control) {
    $widget = new CartQueryWidget($name, [$control => 'pcw_cart_products'], [$control => ['type' => 'select', 'options' => ['product' => 'Products']]]);
    $query->add_source($widget);
    check(($widget->updates[$control]['options'] ?? []) === ['product' => 'Products', 'pcw_cart_products' => 'Current Cart Products'], "$name source preserves options");
    $empty = $query->filter_query(['post_type' => 'pcw_cart_products'], $widget);
    check($empty['post__in'] === [0] && $empty['post_type'] === 'product' && $empty['post_status'] === 'publish', "$name unavailable cart fails closed");
}
$bad = new CartQueryWidget('posts', [], ['posts_post_type' => ['type' => 'text']]); $query->add_source($bad);
check(!$bad->updates, 'incompatible control left unchanged');
$cart = new TestCart(); $GLOBALS['wc'] = (object) ['cart' => $cart];
$widget = new CartQueryWidget('loop-grid', ['post_query_post_type' => 'pcw_cart_products']);
$cart->items = [['product_id' => 7, 'variation_id' => 22], ['product_id' => 7, 'variation_id' => 23], ['product_id' => 9], ['product_id' => 0], []];
$args = ['post__in' => [7, 9, 10], 'post__not_in' => [9], 'tax_query' => ['custom'], 'posts_per_page' => 2];
$result = $query->filter_query($args, $widget);
check($result['post__in'] === [7] && !isset($result['post__not_in']) && $result['tax_query'] === ['custom'] && $result['posts_per_page'] === 2, 'deduplicates parents and respects existing includes and exclusions');
check($query->filter_query(['post__in' => [12]], $widget)['post__in'] === [0], 'empty intersection remains empty');
$otherCart = new TestCart(); $otherCart->items = [['product_id' => 18]]; $GLOBALS['wc']->cart = $otherCart;
check($query->filter_query([], $widget)['post__in'] === [18], 'uses current request cart, not an earlier cart');
$GLOBALS['wc']->cart = $cart;
$cart->items = [];
check($query->filter_query([], $widget)['post__in'] === [0], 'empty cart returns no products');
$other = new CartQueryWidget('posts', ['posts_post_type' => 'product']);
check($query->filter_query($args, $other) === $args, 'other sources untouched');
$other = new CartQueryWidget('unknown', ['post_query_post_type' => 'pcw_cart_products']);
check($query->filter_query($args, $other) === $args, 'other widgets untouched');
