<?php
/** Test fixture installed only into the disposable CI site. */
add_action('wp_enqueue_scripts', static function () { wp_enqueue_script('wc-cart-fragments'); });
add_shortcode('pcw_test_mini_cart', static function () {
    ob_start(); echo '<div class="widget_shopping_cart_content">'; woocommerce_mini_cart(); echo '</div>'; return ob_get_clean();
});
