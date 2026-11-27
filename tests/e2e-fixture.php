<?php
/** Disposable fixture: only run under WP-CLI in the CI test site. */
if (getenv('PCW_TEST_ENV') !== '1' || !defined('WP_CLI') || !WP_CLI) { throw new RuntimeException('Disposable WP-CLI test site required.'); }
update_option('pcw_enabled_widgets', ['phehrest_ajax_smart_add_to_cart' => true], false);
update_option('woocommerce_enable_ajax_add_to_cart', 'yes');
// Verify the actual Elementor control registry, not just rendered CSS.
try {
$widgetInstance = new \Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart\Widget();
foreach (['icon_add', 'icon_position', 'icon_size', 'gap', 'add_icon_color', 'hover_icon_color', 'animate', 'transition', 'one_background', 'one_border', 'one_padding', 'one_radius', 'multiple_background', 'multiple_border', 'multiple_padding', 'multiple_radius', 'shadow_box_shadow_type', 'counter_shadow_box_shadow_type'] as $removed) {
    if ($widgetInstance->get_controls($removed) !== null) { throw new RuntimeException("Unexpected removed widget control: $removed"); }
}
foreach (['counter_icon_size', 'step_padding', 'step_radius', 'step_border_width', 'step_background', 'step_hover_color', 'step_hover_background', 'step_hover_border_color', 'quantity_color', 'quantity_background', 'quantity_padding', 'quantity_radius', 'counter_typography_typography'] as $required) {
    if ($widgetInstance->get_controls($required) === null) { throw new RuntimeException("Missing widget control: $required"); }
}
} catch (Throwable $error) {
    echo '::error title=Elementor controls::' . str_replace(["\r", "\n"], ' ', $error->getMessage()) . "\n";
    throw $error;
}
$product = new WC_Product_Simple();
$product->set_name('محصول آزمایشی فهرست'); $product->set_status('publish'); $product->set_regular_price('20');
$product->set_manage_stock(true); $product->set_stock_quantity(20); $productId = $product->save();
$out = new WC_Product_Simple();
$out->set_name('محصول ناموجود آزمایشی'); $out->set_status('publish'); $out->set_regular_price('18');
$out->set_manage_stock(true); $out->set_stock_quantity(0); $outId = $out->save();
$page = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'تست ابزارک فهرست']);
$widget = static function ($id, $productId) {
    $settings = ['source' => 'selected', 'product_id' => $productId];
    if ($id === 'pcwout01') {
        $settings += ['add_min_width' => ['unit' => 'px', 'size' => 180], 'add_min_width_mobile' => ['unit' => 'px', 'size' => 140], 'step_padding' => ['unit' => 'px', 'top' => '5', 'right' => '8', 'bottom' => '5', 'left' => '8', 'isLinked' => false], 'counter_icon_size' => ['unit' => 'px', 'size' => 24], 'step_background' => '#112233', 'step_hover_background' => '#445566', 'step_hover_color' => '#ffffff', 'quantity_color' => '#123456'];
    }
    return ['id' => $id, 'elType' => 'widget', 'widgetType' => 'phehrest_ajax_smart_add_to_cart', 'settings' => $settings, 'elements' => []];
};
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
