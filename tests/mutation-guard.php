<?php
/** Isolated guard test; no WordPress database or cart is modified. */
define('ABSPATH', __DIR__ . '/');
final class TestResponse extends RuntimeException {
    public array $payload;
    public int $status;
    public function __construct(array $payload, int $status) { $this->payload = $payload; $this->status = $status; parent::__construct('JSON response'); }
}
final class WC_Session_Handler { public function get_session_cookie() { return ['guest-id']; } }
final class FakeDB {
    public string $prefix = 'wp_';
    public $lockResult = null;
    public function prepare($sql, ...$args) { return $sql; }
    public function get_var($sql) { return strpos($sql, 'GET_LOCK') !== false ? $this->lockResult : 1; }
}
$GLOBALS['wpdb'] = new FakeDB();
$GLOBALS['pcw_shutdown'] = [];
function wp_doing_ajax() { return true; }
function apply_filters($name, $value) { return $value; }
function is_user_logged_in() { return false; }
function wp_salt($scheme) { return 'test-secret'; }
function __($text, $domain = '') { return $text; }
function wp_send_json_error($data, $status) { throw new TestResponse($data, $status); }
function add_action($name, $callback, $priority = 10) { $GLOBALS['pcw_shutdown'][] = [$name, $priority]; }
require dirname(__DIR__) . '/widgets/ajax-smart-add-to-cart/src/MutationGuard.php';
use Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart\MutationGuard;
$_POST['action'] = 'pcw_cart_change';
$guard = new MutationGuard();
foreach ([[0, 409, 'pcw_cart_busy'], [null, 503, 'pcw_lock_unavailable']] as [$result, $status, $code]) {
    $GLOBALS['wpdb']->lockResult = $result;
    try { $guard->acquire(); throw new RuntimeException('Guard did not fail closed'); }
    catch (TestResponse $e) {
        if ($e->status !== $status || ($e->payload['code'] ?? '') !== $code) { throw new RuntimeException('Incorrect lock error classification'); }
    }
    if ($GLOBALS['pcw_shutdown']) { throw new RuntimeException('A failed lock registered a release hook'); }
}
$GLOBALS['wpdb']->lockResult = 1;
$guard->acquire();
if ($GLOBALS['pcw_shutdown'] !== [['shutdown', PHP_INT_MAX]]) { throw new RuntimeException('Lock is not retained until shutdown'); }
MutationGuard::release();
if (!MutationGuard::lock('x', 2)) { throw new RuntimeException('Expected successful ledger lock'); }
$GLOBALS['wpdb']->lockResult = null;
if (MutationGuard::lock('x', 2)) { throw new RuntimeException('Unavailable ledger lock did not fail closed'); }
echo "PASS: guard distinguishes contention from unavailable locks and fails closed\n";
