<?php
namespace Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart;
if (!defined('ABSPATH')) { exit; }
/** Bounded, expiring plugin-owned ledger. Stores hashed identity, cart line key and quantity only. */
final class IdempotencyStore {
    private const OPTION = 'pcw_idempotency';
    private const TTL = 900;
    private const LIMIT = 4096;
    private function with_lock(callable $fn) {
        $lock = MutationGuard::lock_name('idempotency-ledger');
        if (!MutationGuard::lock($lock, 2)) { throw new CartException(__('ثبت درخواست در دسترس نیست؛ دوباره تلاش کنید.', 'phehrest-custom-widgets')); }
        try {
            wp_cache_delete(self::OPTION, 'options');
            $records = get_option(self::OPTION, []);
            $records = is_array($records) ? $records : [];
            foreach ($records as $key => $record) { if (!is_array($record) || (int) ($record['expires'] ?? 0) < time()) { unset($records[$key]); } }
            $before = $records;
            $result = $fn($records);
            if ($before !== $records && !update_option(self::OPTION, $records, false)) {
                wp_cache_delete(self::OPTION, 'options');
                if (get_option(self::OPTION, []) !== $records) { throw new CartException(__('ثبت امن درخواست ممکن نشد؛ سبد خرید را بررسی کنید.', 'phehrest-custom-widgets')); }
            }
            return $result;
        } finally { MutationGuard::unlock($lock); }
    }
    public function reserve(string $requestId, string $payload): ?array {
        if (!preg_match('/^[a-f0-9]{32}$/', $requestId)) { throw new CartException(__('شناسه درخواست معتبر نیست.', 'phehrest-custom-widgets')); }
        $key = hash_hmac('sha256', MutationGuard::customer() . ':' . $requestId, wp_salt('auth'));
        $signature = hash_hmac('sha256', $payload, wp_salt('auth'));
        return $this->with_lock(static function (&$records) use ($key, $signature) {
            if (isset($records[$key])) {
                $record = $records[$key];
                if (!hash_equals((string) $record['signature'], $signature)) { throw new CartException(__('شناسه درخواست قبلاً استفاده شده است.', 'phehrest-custom-widgets')); }
                if (empty($record['result'])) { throw new CartException(__('نتیجه درخواست قبلی نامشخص است؛ ابتدا سبد خرید را بررسی کنید.', 'phehrest-custom-widgets')); }
                return $record['result'];
            }
            if (count($records) >= self::LIMIT) { throw new CartException(__('سامانه موقتاً مشغول است؛ کمی بعد دوباره تلاش کنید.', 'phehrest-custom-widgets')); }
            $records[$key] = ['expires' => time() + self::TTL, 'signature' => $signature, 'result' => null];
            return null;
        });
    }
    public function complete(string $requestId, array $result): void {
        $key = hash_hmac('sha256', MutationGuard::customer() . ':' . $requestId, wp_salt('auth'));
        $this->with_lock(static function (&$records) use ($key, $result) {
            if (!isset($records[$key])) { throw new CartException(__('ثبت نتیجه درخواست ممکن نشد؛ سبد خرید را بررسی کنید.', 'phehrest-custom-widgets')); }
            // Only the line key and quantity are needed for a replay; no customer or product data is stored.
            $records[$key]['result'] = ['key' => (string) $result['key'], 'quantity' => (int) $result['quantity']];
            return null;
        });
    }
}
