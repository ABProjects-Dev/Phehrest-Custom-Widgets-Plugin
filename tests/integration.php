<?php
/** Run ONLY on a disposable site: PCW_TEST_ENV=1 wp eval-file tests/integration.php */
if (getenv('PCW_TEST_ENV') !== '1' || !defined('WP_CLI') || !WP_CLI) { throw new RuntimeException('Disposable WP-CLI test environment required.'); }
if (!class_exists('WooCommerce') || !defined('ELEMENTOR_VERSION')) { throw new RuntimeException('WooCommerce and Elementor must be active.'); }
if (!WC()->cart) { wc_load_cart(); }
WC()->cart->empty_cart();
$product = new WC_Product_Simple();
$product->set_name('PCW disposable test product'); $product->set_status('publish'); $product->set_regular_price('20');
$product->set_manage_stock(true); $product->set_stock_quantity(2); $id = $product->save();
$service = new \Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart\CartService();
function pcw_assert($condition, $message) { if (!$condition) { throw new RuntimeException($message); } echo "PASS: $message\n"; }
try {
    $state = $service->change($id, 0, [], 'add', '', 0);
    pcw_assert($state['quantity'] === 1 && WC()->cart->get_cart_contents_count() === 1, 'simple add');
    $key = $state['key'];
    $state = $service->change($id, 0, [], 'increase', $key, 1);
    pcw_assert($state['quantity'] === 2 && (float) WC()->cart->get_subtotal() === 40.0, 'increase and totals');
    $blocked = false;
    try { $service->change($id, 0, [], 'increase', $key, 2); } catch (RuntimeException $e) { $blocked = true; }
    pcw_assert($blocked, 'stock limit');
    $state = $service->change($id, 0, [], 'decrease', $key, 2);
    pcw_assert($state['quantity'] === 1, 'decrease');
    $state = $service->change($id, 0, [], 'remove', $key, 1);
    pcw_assert($state['quantity'] === 0 && WC()->cart->is_empty(), 'remove');
} finally { WC()->cart->empty_cart(); wp_delete_post($id, true); }
