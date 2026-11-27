<?php
namespace Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart;
if (!defined('ABSPATH')) { exit; }
/** Serialize this plugin's requests before WooCommerce loads its cart/session snapshot. */
final class MutationGuard {
    private static string $customer = '';
    private static string $lock = '';
    public function acquire(): void {
        $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
        if (!wp_doing_ajax() || !in_array($action, ['pcw_cart_state', 'pcw_cart_change'], true)) { return; }
        $handler = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
        // A custom session handler may use a completely different identity or persistence model.
        if ($handler !== 'WC_Session_Handler' || !class_exists('WC_Session_Handler')) { if ($action === 'pcw_cart_change') { $this->deny(); } return; }
        $session = new \WC_Session_Handler();
        $cookie = $session->get_session_cookie();
        if (!$cookie || empty($cookie[0])) { if ($action === 'pcw_cart_change') { $this->deny(); } return; }
        self::$customer = is_user_logged_in() ? 'user:' . get_current_user_id() : 'guest:' . $cookie[0];
        self::$lock = self::lock_name('customer:' . self::$customer);
        if (!self::lock(self::$lock, 0)) { wp_send_json_error(['message' => __('درخواست دیگری در حال اجراست؛ کمی صبر کنید و دوباره تلاش کنید.', 'phehrest-custom-widgets')], 409); }
        // WooCommerce saves its session on shutdown at priority 20. Keep the lock until afterwards.
        add_action('shutdown', [self::class, 'release'], PHP_INT_MAX);
    }
    private function deny(): void { wp_send_json_error(['message' => __('نشست سبد خرید یا روش ذخیره آن پشتیبانی نمی‌شود؛ وضعیت را دوباره بارگذاری کنید.', 'phehrest-custom-widgets')], 403); }
    public static function customer(): string {
        if (self::$customer === '' || self::$lock === '') { throw new CartException(__('نشست سبد خرید معتبر نیست.', 'phehrest-custom-widgets')); }
        return self::$customer;
    }
    public static function lock_name(string $name): string {
        global $wpdb;
        return 'pcw:' . substr(hash_hmac('sha256', $wpdb->prefix . ':' . $name, wp_salt('auth')), 0, 56);
    }
    public static function lock(string $name, int $seconds): bool {
        global $wpdb;
        // MySQL advisory locks do not read or alter any WordPress/WooCommerce tables.
        return (string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, %d)', $name, $seconds)) === '1';
    }
    public static function unlock(string $name): void {
        global $wpdb;
        $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $name));
    }
    public static function release(): void { if (self::$lock !== '') { self::unlock(self::$lock); self::$lock = ''; } }
}
