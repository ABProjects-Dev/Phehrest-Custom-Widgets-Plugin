<?php
/** Run ONLY on a disposable site: PCW_TEST_ENV=1 wp eval-file tests/integration.php */
if (getenv('PCW_TEST_ENV') !== '1' || !defined('WP_CLI') || !WP_CLI) { throw new RuntimeException('Disposable WP-CLI test environment required.'); }
if (!class_exists('WooCommerce') || !defined('ELEMENTOR_VERSION')) { throw new RuntimeException('WooCommerce and Elementor must be active.'); }
if (!WC()->cart) { wc_load_cart(); }
WC()->cart->empty_cart();
$product = new WC_Product_Simple();
$product->set_name('PCW disposable test product'); $product->set_status('publish'); $product->set_regular_price('20'); $product->set_sale_price('15');
$product->set_manage_stock(true); $product->set_stock_quantity(2); $id = $product->save();
$service = new \Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart\CartService();
function pcw_assert($condition, $message) { if (!$condition) { throw new RuntimeException($message); } echo "PASS: $message\n"; }
try {
    $state = $service->change($id, 0, [], 'add', '', 0);
    pcw_assert($state['quantity'] === 1 && WC()->cart->get_cart_contents_count() === 1, 'simple add');
    $key = $state['key'];
    $state = $service->change($id, 0, [], 'increase', $key, 1);
    pcw_assert($state['quantity'] === 2 && (float) WC()->cart->get_subtotal() === 30.0, 'increase and discounted totals');
    $blocked = false;
    try { $service->change($id, 0, [], 'increase', $key, 2); } catch (RuntimeException $e) { $blocked = true; }
    pcw_assert($blocked, 'stock limit');
    $state = $service->change($id, 0, [], 'decrease', $key, 2);
    pcw_assert($state['quantity'] === 1, 'decrease');
    $state = $service->change($id, 0, [], 'remove', $key, 1);
    pcw_assert($state['quantity'] === 0 && WC()->cart->is_empty(), 'remove');
    $ordinaryKey = WC()->cart->add_to_cart($id, 1);
    [$foundKey, $foundItem] = $service->find($id, 0, []);
    pcw_assert($foundKey === $ordinaryKey && (int) $foundItem['quantity'] === 1, 'recognizes a standard WooCommerce cart item');
    WC()->cart->empty_cart();
    WC()->cart->add_to_cart($id, 1, 0, [], ['pcw_test_extra' => 'first']);
    WC()->cart->add_to_cart($id, 1, 0, [], ['pcw_test_extra' => 'second']);
    $blocked = false;
    try { $service->find($id, 0, []); } catch (RuntimeException $e) { $blocked = true; }
    pcw_assert($blocked && WC()->cart->get_cart_contents_count() === 2, 'ambiguous custom cart lines not merged or altered');
    WC()->cart->empty_cart();
    $product->set_sold_individually(true); $product->save();
    $state = $service->change($id, 0, [], 'add', '', 0);
    $blocked = false;
    try { $service->change($id, 0, [], 'increase', $state['key'], 1); } catch (RuntimeException $e) { $blocked = true; }
    pcw_assert($blocked, 'sold individually blocks increase');
    WC()->cart->empty_cart();
    $product->set_sold_individually(false); $product->set_stock_quantity(0); $product->set_stock_status('outofstock'); $product->set_backorders('no'); $product->save();
    $blocked = false;
    try { $service->change($id, 0, [], 'add', '', 0); } catch (RuntimeException $e) { $blocked = true; }
    pcw_assert($blocked, 'out of stock is rejected');
    $product->set_backorders('yes'); $product->set_stock_status('onbackorder'); $product->save();
    $state = $service->change($id, 0, [], 'add', '', 0);
    pcw_assert($state['quantity'] === 1, 'backorder is allowed');
    WC()->cart->empty_cart();
} finally { WC()->cart->empty_cart(); wp_delete_post($id, true); }

// Validate a real variable product and variation against WooCommerce's product data store.
$attribute = new WC_Product_Attribute();
$attribute->set_name('Color'); $attribute->set_options(['red', 'blue']); $attribute->set_variation(true); $attribute->set_visible(true);
$variable = new WC_Product_Variable();
$variable->set_name('PCW disposable variable product'); $variable->set_status('publish'); $variable->set_attributes([$attribute]);
$variableId = $variable->save();
$variation = new WC_Product_Variation();
$variation->set_parent_id($variableId); $variation->set_status('publish'); $variation->set_regular_price('12'); $variation->set_attributes(['color' => 'red']);
$variationId = $variation->save();
try {
    $rejected = false;
    try { $service->change($variableId, $variationId, ['attribute_color' => 'blue'], 'add', '', 0); } catch (RuntimeException $e) { $rejected = true; }
    pcw_assert($rejected && WC()->cart->is_empty(), 'invalid variation rejected');
    $state = $service->change($variableId, $variationId, ['attribute_color' => 'red'], 'add', '', 0);
    pcw_assert($state['quantity'] === 1 && WC()->cart->get_cart_contents_count() === 1, 'valid variation added');
    $cartItem = WC()->cart->get_cart_item($state['key']);
    pcw_assert((int) $cartItem['variation_id'] === $variationId && $cartItem['variation']['attribute_color'] === 'red', 'variation identity retained');
    $service->change($variableId, $variationId, ['attribute_color' => 'red'], 'remove', $state['key'], 1);
    pcw_assert(WC()->cart->is_empty(), 'variation removed');
} finally { WC()->cart->empty_cart(); wp_delete_post($variationId, true); wp_delete_post($variableId, true); }
