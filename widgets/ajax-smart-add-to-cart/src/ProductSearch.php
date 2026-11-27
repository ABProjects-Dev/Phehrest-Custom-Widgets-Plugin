<?php
namespace Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart;
if (!defined('ABSPATH')) { exit; }
final class ProductSearch {
    public function search(): void {
        if (!current_user_can('edit_products') || !current_user_can('edit_posts')) { wp_send_json_error(['message' => __('دسترسی غیرمجاز.', 'phehrest-custom-widgets')], 403); }
        check_ajax_referer('pcw_product_search', 'nonce');
        $term = isset($_POST['term']) && is_string($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';
        $length = function_exists('mb_strlen') ? mb_strlen($term) : strlen($term);
        if ($length < 2 || $length > 100) { wp_send_json_success([]); }
        $ids = \WC_Data_Store::load('product')->search_products($term, '', false, false, 20);
        $results = [];
        foreach ($ids as $id) {
            $product = wc_get_product($id);
            if ($product && $product->get_status() === 'publish' && $product->is_type(['simple', 'variable'])) {
                $results[] = ['id' => $product->get_id(), 'name' => $product->get_name()];
            }
        }
        wp_send_json_success($results);
    }
}
