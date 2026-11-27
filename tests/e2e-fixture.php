<?php
/** Disposable fixture: only run under WP-CLI in the CI test site. */
if (getenv('PCW_TEST_ENV') !== '1' || !defined('WP_CLI') || !WP_CLI) { throw new RuntimeException('Disposable WP-CLI test site required.'); }
update_option('pcw_enabled_widgets', ['phehrest_ajax_smart_add_to_cart' => true], false);
update_option('woocommerce_enable_ajax_add_to_cart', 'yes');
$product = new WC_Product_Simple();
$product->set_name('محصول آزمایشی فهرست'); $product->set_status('publish'); $product->set_regular_price('20');
$product->set_manage_stock(true); $product->set_stock_quantity(20); $productId = $product->save();
$out = new WC_Product_Simple();
$out->set_name('محصول ناموجود آزمایشی'); $out->set_status('publish'); $out->set_regular_price('18');
$out->set_manage_stock(true); $out->set_stock_quantity(0); $outId = $out->save();
$page = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'تست ابزارک فهرست']);
$widget = static function ($id, $productId) { return ['id' => $id, 'elType' => 'widget', 'widgetType' => 'phehrest_ajax_smart_add_to_cart', 'settings' => ['source' => 'selected', 'product_id' => $productId], 'elements' => []]; };
$elements = [[
    'id' => 'pcwsect1', 'elType' => 'section', 'settings' => [], 'elements' => [[
        'id' => 'pcwcol01', 'elType' => 'column', 'settings' => ['_column_size' => 100], 'elements' => [
            ['id' => 'pcwtitle', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => ['title' => 'تجربه خرید آسان'], 'elements' => []],
            $widget('pcwmain1', $productId),
            ['id' => 'pcwmini1', 'elType' => 'widget', 'widgetType' => 'shortcode', 'settings' => ['shortcode' => '[pcw_test_mini_cart]'], 'elements' => []],
            ['id' => 'pcwstd01', 'elType' => 'widget', 'widgetType' => 'shortcode', 'settings' => ['shortcode' => '[pcw_test_standard_button product="' . $productId . '"]'], 'elements' => []],
            $widget('pcwout01', $outId),
        ],
    ]],
]];
update_post_meta($page, '_elementor_edit_mode', 'builder');
update_post_meta($page, '_elementor_template_type', 'wp-page');
update_post_meta($page, '_elementor_version', ELEMENTOR_VERSION);
update_post_meta($page, '_elementor_data', wp_slash(wp_json_encode($elements)));
file_put_contents('/tmp/pcw-fixture.json', wp_json_encode(['page' => $page, 'product' => $productId, 'out' => $outId]));
echo "Created disposable Elementor page $page and products $productId, $outId\n";
