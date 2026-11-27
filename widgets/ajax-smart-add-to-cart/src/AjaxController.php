<?php
namespace Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart;
if (!defined('ABSPATH')) { exit; }
final class AjaxController {
    private function prepare(): void {
        nocache_headers();
        if (!function_exists('wc_load_cart')) { wp_send_json_error(['message' => __('فروشگاه در دسترس نیست.', 'phehrest-custom-widgets')], 503); }
        if (!WC()->cart || !WC()->session) { wc_load_cart(); }
        if (!WC()->cart || !WC()->session) { wp_send_json_error(['message' => __('سبد خرید در دسترس نیست.', 'phehrest-custom-widgets')], 503); }
        WC()->session->set_customer_session_cookie(true);
    }
    private function token(int $tick): string {
        return hash_hmac('sha256', 'pcw|' . WC()->session->get_customer_id() . '|' . $tick, wp_salt('nonce'));
    }
    private function request(): array {
        $productId = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $variationId = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        $attributes = isset($_POST['attributes']) && is_array($_POST['attributes']) ? wp_unslash($_POST['attributes']) : [];
        if (count($attributes) > 30) { throw new CartException(__('گزینه‌های محصول معتبر نیستند.', 'phehrest-custom-widgets')); }
        return [$productId, $variationId, $attributes];
    }
    public function state(): void {
        $this->prepare();
        try {
            [$productId, $variationId, $attributes] = $this->request();
            $service = new CartService();
            [$product, $attributes] = $service->product($productId, $variationId, $attributes);
            [$key, $item] = $service->find($productId, $variationId, $attributes);
            wp_send_json_success(['key' => $key, 'quantity' => $item ? (int) $item['quantity'] : 0,
                'purchasable' => $product->is_purchasable() && $product->is_in_stock(),
                'soldIndividually' => $product->is_sold_individually(),
                'nonce' => wp_create_nonce('pcw_cart_change'), 'token' => $this->token((int) wp_nonce_tick())]);
        } catch (CartException $e) { wp_send_json_error(['message' => $e->getMessage()], 400); }
        catch (\Throwable $e) { wp_send_json_error(['message' => __('خطایی رخ داد؛ وضعیت سبد خرید را بررسی کنید.', 'phehrest-custom-widgets')], 500); }
    }
    public function change(): void {
        $this->prepare();
        check_ajax_referer('pcw_cart_change', 'nonce');
        $token = isset($_POST['token']) && is_string($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
        $tick = (int) wp_nonce_tick();
        if (!$token || (!hash_equals($this->token($tick), $token) && !hash_equals($this->token($tick - 1), $token))) {
            wp_send_json_error(['message' => __('نشست منقضی شده است؛ دوباره تلاش کنید.', 'phehrest-custom-widgets')], 403);
        }
        try {
            [$productId, $variationId, $attributes] = $this->request();
            $operation = isset($_POST['operation']) && is_string($_POST['operation']) ? sanitize_key(wp_unslash($_POST['operation'])) : '';
            $key = isset($_POST['key']) && is_string($_POST['key']) ? sanitize_text_field(wp_unslash($_POST['key'])) : '';
            $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 0;
            $requestId = isset($_POST['request_id']) && is_string($_POST['request_id']) ? sanitize_key(wp_unslash($_POST['request_id'])) : '';
            ksort($attributes);
            $payload = wp_json_encode([$productId, $variationId, $attributes, $operation, $key, $quantity]);
            $ledger = new IdempotencyStore();
            $result = $ledger->reserve($requestId, $payload);
            $replayed = $result !== null;
            if (!$replayed) {
                $result = (new CartService())->change($productId, $variationId, $attributes, $operation, $key, $quantity);
                // Persist the official WooCommerce session before marking the operation complete.
                if (!is_callable([WC()->session, 'save_data'])) { throw new CartException(__('ذخیره نشست در دسترس نیست؛ سبد خرید را بررسی کنید.', 'phehrest-custom-widgets')); }
                WC()->session->save_data();
                $ledger->complete($requestId, $result);
            }
            $result['replayed'] = $replayed;
            ob_start(); woocommerce_mini_cart(); $miniCart = ob_get_clean();
            $result['fragments'] = apply_filters('woocommerce_add_to_cart_fragments', ['div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $miniCart . '</div>']);
            $result['cart_hash'] = WC()->cart->get_cart_hash();
            wp_send_json_success($result);
        } catch (CartException $e) { wp_send_json_error(['message' => $e->getMessage()], 400); }
        catch (\Throwable $e) { wp_send_json_error(['message' => __('خطایی رخ داد؛ وضعیت سبد خرید را بررسی کنید.', 'phehrest-custom-widgets')], 500); }
    }
}
