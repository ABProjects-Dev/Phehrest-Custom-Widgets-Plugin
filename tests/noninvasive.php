<?php
/** Destructive assertions are permitted only in a disposable CI WordPress installation. */
if (getenv('PCW_TEST_ENV') !== '1' || !defined('WP_CLI') || !WP_CLI) { throw new RuntimeException('Disposable test site required.'); }
function pcw_check($condition, $label) { if (!$condition) { throw new RuntimeException($label); } echo "PASS: $label\n"; }
if (getenv('PCW_TEST_PHASE') === 'create') {
    $product = new WC_Product_Simple();
    $product->set_name('PCW retained product'); $product->set_status('publish'); $product->set_regular_price('13'); $productId = $product->save();
    $order = wc_create_order(); $order->add_product($product, 1); $order->calculate_totals(); $order->save();
    $userId = wp_insert_user(['user_login' => 'pcw-retained-user', 'user_pass' => 'disposable-password', 'user_email' => 'pcw-retained@example.test']);
    if (is_wp_error($userId)) { throw new RuntimeException('User fixture failed'); }
    update_option('pcw_enabled_widgets', ['phehrest_ajax_smart_add_to_cart' => true], false);
    update_option('pcw_design_system', ['primary' => '#123456'], false);
    update_option('pcw_idempotency', ['test' => ['expires' => time() + 900]], false);
    file_put_contents('/tmp/pcw-retained.json', wp_json_encode(['product' => $productId, 'order' => $order->get_id(), 'user' => $userId, 'currency' => get_option('woocommerce_currency')]));
    echo "PASS: created disposable non-invasive fixtures\n";
    return;
}
$fixture = json_decode((string) file_get_contents('/tmp/pcw-retained.json'), true);
pcw_check(!has_action('wp_ajax_pcw_cart_change') && !has_action('wp_ajax_nopriv_pcw_cart_change'), 'disabled plugin registers no cart endpoint');
if (!WC()->cart) { wc_load_cart(); }
WC()->cart->empty_cart();
pcw_check((bool) WC()->cart->add_to_cart($fixture['product'], 1), 'native WooCommerce add to cart still works after deactivation');
WC()->cart->empty_cart();
pcw_check(get_option('pcw_enabled_widgets', null) === null && get_option('pcw_design_system', null) === null && get_option('pcw_idempotency', null) === null, 'uninstall removes plugin-owned options only');
$product = wc_get_product($fixture['product']); $order = wc_get_order($fixture['order']);
pcw_check($product && $product->get_name() === 'PCW retained product' && (float) $product->get_price() === 13.0, 'product remains unchanged');
pcw_check($order && count($order->get_items()) === 1 && (float) $order->get_total() === 13.0, 'order remains unchanged');
pcw_check((bool) get_userdata($fixture['user']), 'user remains unchanged');
pcw_check(get_option('woocommerce_currency') === $fixture['currency'], 'store settings remain unchanged');
