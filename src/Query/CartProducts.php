<?php
namespace Phehrest\CustomWidgets\Query;

if (!defined('ABSPATH')) { exit; }

/** Elementor Pro group-query source, independent of the opt-in cart widget. */
final class CartProducts {
    private const SOURCE = 'pcw_cart_products';
    private const CONTROLS = [
        'posts' => 'posts_post_type',
        'loop-grid' => 'post_query_post_type',
        'loop-carousel' => 'post_query_post_type',
    ];

    public function boot(): void {
        foreach (array_keys(self::CONTROLS) as $widget) {
            add_action("elementor/element/{$widget}/section_query/after_section_end", [$this, 'add_source'], 10, 2);
        }
        add_filter('elementor/query/query_args', [$this, 'filter_query'], 100, 2);
    }

    public function add_source($widget, $args = []): void {
        if (!is_object($widget) || !method_exists($widget, 'get_name') || !method_exists($widget, 'get_controls') || !method_exists($widget, 'update_control')) { return; }
        $id = self::CONTROLS[$widget->get_name()] ?? null;
        if (!$id) { return; }
        $control = $widget->get_controls($id);
        if (!is_array($control) || ($control['type'] ?? '') !== 'select' || !isset($control['options']) || !is_array($control['options'])) { return; }
        $options = $control['options'];
        $options[self::SOURCE] = __('Current Cart Products', 'phehrest-custom-widgets');
        $widget->update_control($id, ['options' => $options]);
    }

    public function filter_query($args, $widget) {
        if (!is_array($args) || !is_object($widget) || !method_exists($widget, 'get_name') || !method_exists($widget, 'get_settings')) { return $args; }
        $control = self::CONTROLS[$widget->get_name()] ?? null;
        if (!$control || $widget->get_settings($control) !== self::SOURCE) { return $args; }

        $ids = $this->cart_product_ids();
        // WP_Query does not combine post__in and post__not_in; apply exclusions first.
        if (!empty($args['post__in']) && is_array($args['post__in'])) {
            $ids = array_intersect($ids, array_map('intval', $args['post__in']));
        }
        if (!empty($args['post__not_in']) && is_array($args['post__not_in'])) {
            $ids = array_diff($ids, array_map('intval', $args['post__not_in']));
        }
        unset($args['post__not_in']);
        $args['post_type'] = 'product';
        $args['post_status'] = 'publish';
        $args['post__in'] = $ids ? array_values($ids) : [0];
        $args['ignore_sticky_posts'] = true;
        return $args;
    }

    private function cart_product_ids(): array {
        if (!function_exists('WC')) { return []; }
        try {
            $wc = WC();
            if (!is_object($wc) || !isset($wc->cart) || !is_object($wc->cart) || !method_exists($wc->cart, 'get_cart')) { return []; }
            $items = $wc->cart->get_cart();
            if (!is_array($items)) { return []; }
            $ids = [];
            foreach ($items as $item) {
                // WooCommerce stores a variation's parent in product_id.
                $id = is_array($item) && isset($item['product_id']) ? (int) $item['product_id'] : 0;
                if ($id > 0) { $ids[$id] = $id; }
            }
            return array_values($ids);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
