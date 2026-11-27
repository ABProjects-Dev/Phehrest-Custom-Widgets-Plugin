<?php
namespace Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart;
if (!defined('ABSPATH')) { exit; }

/** All mutations use WooCommerce's public cart API. The counter addresses one cart line. */
final class CartService {
    private function fail(string $message): void { throw new CartException($message); }
    public function product(int $productId, int $variationId, array $attributes): array {
        $parent = wc_get_product($productId);
        if (!$parent || $parent->get_status() !== 'publish') { $this->fail(__('محصول معتبر یا قابل مشاهده نیست.', 'phehrest-custom-widgets')); }
        if (!$parent->is_type(['simple', 'variable'])) { $this->fail(__('این نوع محصول با شمارنده سازگار نیست؛ از صفحه محصول استفاده کنید.', 'phehrest-custom-widgets')); }
        if ($parent->is_type('simple')) {
            if ($variationId || $attributes) { $this->fail(__('گزینه‌های محصول معتبر نیستند.', 'phehrest-custom-widgets')); }
            return [$parent, []];
        }
        if (!$variationId) { $this->fail(__('ابتدا ویژگی‌های محصول را انتخاب کنید.', 'phehrest-custom-widgets')); }
        $variation = wc_get_product($variationId);
        if (!$variation || !$variation->is_type('variation') || $variation->get_parent_id() !== $productId || $variation->get_status() !== 'publish') {
            $this->fail(__('تنوع انتخاب‌شده معتبر نیست.', 'phehrest-custom-widgets'));
        }
        $clean = [];
        $variationAttributes = $variation->get_variation_attributes();
        foreach ($parent->get_attributes() as $attribute) {
            if (!$attribute->get_variation()) { continue; }
            $key = 'attribute_' . sanitize_title($attribute->get_name());
            $value = isset($attributes[$key]) && is_scalar($attributes[$key]) ? (string) $attributes[$key] : '';
            $value = $attribute->is_taxonomy() ? sanitize_title($value) : wp_strip_all_tags(html_entity_decode($value, ENT_QUOTES, get_bloginfo('charset')));
            if (strlen($value) > 200 || ($value === '' && $value !== '0')) { $this->fail(__('همه ویژگی‌های محصول را انتخاب کنید.', 'phehrest-custom-widgets')); }
            $valid = $variationAttributes[$key] ?? '';
            if (($valid !== '' && $valid !== $value) || ($valid === '' && !in_array($value, $attribute->get_slugs(), true))) {
                $this->fail(__('ویژگی انتخاب‌شده برای این تنوع معتبر نیست.', 'phehrest-custom-widgets'));
            }
            $clean[$key] = $value;
        }
        if (count($clean) !== count($attributes)) { $this->fail(__('گزینه‌های محصول معتبر نیستند.', 'phehrest-custom-widgets')); }
        $matched = \WC_Data_Store::load('product')->find_matching_product_variation($parent, $clean);
        if ((int) $matched !== $variationId) { $this->fail(__('تنوع انتخاب‌شده معتبر نیست.', 'phehrest-custom-widgets')); }
        return [$variation, $clean];
    }
    public function find(int $productId, int $variationId, array $attributes): array {
        $matches = [];
        ksort($attributes);
        foreach (WC()->cart->get_cart() as $key => $item) {
            $itemAttributes = $item['variation'] ?? [];
            ksort($itemAttributes);
            if ((int) $item['product_id'] === $productId && (int) $item['variation_id'] === $variationId && $itemAttributes === $attributes) { $matches[$key] = $item; }
        }
        if (count($matches) > 1) { $this->fail(__('چند ردیف متفاوت برای این محصول در سبد وجود دارد؛ از صفحه سبد خرید استفاده کنید.', 'phehrest-custom-widgets')); }
        if (!$matches) { return ['', null]; }
        $key = (string) array_key_first($matches);
        $item = $matches[$key];
        if (!is_numeric($item['quantity']) || (float) $item['quantity'] !== (float) (int) $item['quantity']) {
            $this->fail(__('تعداد این محصول با شمارنده سازگار نیست؛ از صفحه سبد خرید استفاده کنید.', 'phehrest-custom-widgets'));
        }
        return [$key, $item];
    }
    public function change(int $productId, int $variationId, array $attributes, string $operation, string $expectedKey, int $expectedQuantity): array {
        [$product, $attributes] = $this->product($productId, $variationId, $attributes);
        [$key, $item] = $this->find($productId, $variationId, $attributes);
        $quantity = $item ? (int) $item['quantity'] : 0;
        if ($expectedQuantity !== $quantity || $expectedKey !== $key) { $this->fail(__('سبد خرید تغییر کرده است؛ وضعیت به‌روزرسانی شد. دوباره تلاش کنید.', 'phehrest-custom-widgets')); }
        if (!in_array($operation, ['add', 'increase', 'decrease', 'remove'], true)) { $this->fail(__('درخواست نامعتبر است.', 'phehrest-custom-widgets')); }
        if (($operation === 'add' && $quantity !== 0) || ($operation !== 'add' && $quantity === 0)) { $this->fail(__('وضعیت سبد خرید تغییر کرده است.', 'phehrest-custom-widgets')); }
        if ($operation === 'remove' && $quantity !== 1) { $this->fail(__('برای کاهش تعداد از دکمه منفی استفاده کنید.', 'phehrest-custom-widgets')); }
        $target = $operation === 'add' ? 1 : ($operation === 'increase' ? $quantity + 1 : $quantity - 1);
        // Honor the same quantity input filters used by the store. Custom step sizes are
        // intentionally not approximated by this one-unit control.
        $args = function_exists('wc_get_quantity_input_args') ? wc_get_quantity_input_args([], $product) : apply_filters('woocommerce_quantity_input_args', [
            'input_id' => 'pcw-quantity', 'input_name' => 'quantity', 'input_value' => '1',
            'classes' => ['input-text', 'qty', 'text'], 'product_name' => $product->get_title(),
            'pattern' => '[0-9]*', 'inputmode' => 'numeric', 'placeholder' => '', 'autocomplete' => 'off',
            'min_value' => apply_filters('woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product),
            'max_value' => apply_filters('woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product),
            'step' => apply_filters('woocommerce_quantity_input_step', 1, $product), 'readonly' => false,
        ], $product);
        $min = isset($args['min_value']) && is_numeric($args['min_value']) ? (float) $args['min_value'] : 1;
        $max = isset($args['max_value']) && is_numeric($args['max_value']) ? (float) $args['max_value'] : -1;
        $step = $args['step'] ?? 1;
        if ($target > 0 && (!empty($args['readonly']) || !is_numeric($step) || (float) $step !== 1.0 || $target < $min || (($operation === 'add' || $operation === 'increase') && $max > 0 && $target > $max))) {
            $this->fail(__('این تعداد با محدودیت خرید محصول سازگار نیست؛ از صفحه سبد خرید استفاده کنید.', 'phehrest-custom-widgets'));
        }
        if ($operation === 'add' || $operation === 'increase') {
            if (!$product->is_purchasable() || !$product->is_in_stock() || ($product->is_sold_individually() && $target > 1)) { $this->fail(__('این محصول با تعداد درخواستی قابل خرید نیست.', 'phehrest-custom-widgets')); }
            $cartQuantities = WC()->cart->get_cart_item_quantities();
            $stockId = $product->get_stock_managed_by_id();
            if (!$product->has_enough_stock(($cartQuantities[$stockId] ?? 0) + 1)) { $this->fail(__('موجودی برای این تعداد کافی نیست.', 'phehrest-custom-widgets')); }
        }
        if ($operation === 'add') {
            if (!apply_filters('woocommerce_add_to_cart_validation', true, $productId, 1, $variationId, $attributes)) { $this->fail(__('افزودن محصول توسط قوانین فروشگاه مجاز نیست.', 'phehrest-custom-widgets')); }
            $newKey = WC()->cart->add_to_cart($productId, 1, $variationId, $attributes);
            if (!$newKey) { $this->fail(__('افزودن محصول ممکن نشد. موجودی و گزینه‌های محصول را بررسی کنید.', 'phehrest-custom-widgets')); }
            do_action('woocommerce_ajax_added_to_cart', $productId);
        } else {
            if (!apply_filters('woocommerce_update_cart_validation', true, $key, $item, $target)) { $this->fail(__('تغییر تعداد توسط قوانین فروشگاه مجاز نیست.', 'phehrest-custom-widgets')); }
            if ($target === 0) { $ok = WC()->cart->remove_cart_item($key); WC()->cart->calculate_totals(); }
            else { $ok = WC()->cart->set_quantity($key, $target, true); }
            if (!$ok) { $this->fail(__('به‌روزرسانی سبد خرید ممکن نشد.', 'phehrest-custom-widgets')); }
        }
        [$newKey, $newItem] = $this->find($productId, $variationId, $attributes);
        return ['key' => $newKey, 'quantity' => $newItem ? (int) $newItem['quantity'] : 0];
    }
}
