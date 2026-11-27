<?php
/** Test fixture installed only into the disposable CI site. */
add_action('wp_enqueue_scripts', static function () { wp_enqueue_script('wc-cart-fragments'); wp_enqueue_script('wc-add-to-cart'); });
add_shortcode('pcw_test_mini_cart', static function () {
    ob_start(); echo '<div class="widget_shopping_cart_content">'; woocommerce_mini_cart(); echo '</div>'; return ob_get_clean();
});

// A separate standard WooCommerce button ensures the widget does not alter other buttons.
add_shortcode('pcw_test_standard_button', static function ($atts) {
    $id = absint($atts['product'] ?? 0);
    return '<div class="pcw-test-standard"><a href="' . esc_url(add_query_arg('add-to-cart', $id, home_url('/'))) . '" data-product_id="' . esc_attr($id) . '" data-quantity="1" class="button product_type_simple add_to_cart_button ajax_add_to_cart">Standard add to cart</a></div>';
});
