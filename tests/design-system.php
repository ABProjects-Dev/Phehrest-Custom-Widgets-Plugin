<?php
define('ABSPATH', __DIR__ . '/');
function sanitize_hex_color($value) { return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $value) ? $value : null; }
function get_option($key, $default = false) { return $GLOBALS['test_option'] ?? $default; }
require dirname(__DIR__) . '/src/DesignSystem.php';
use Phehrest\CustomWidgets\DesignSystem;
$values = DesignSystem::sanitize(['primary' => '#123456', 'text' => 'red;position:fixed', 'radius' => 900, 'duration' => -10, 'font' => 'unsafe', 'shadow' => 'medium']);
if ($values !== ['primary' => '#123456', 'radius' => 80, 'duration' => 0, 'shadow' => 'medium']) { throw new RuntimeException('Invalid design defaults were accepted.'); }
$GLOBALS['test_option'] = $values;
$style = DesignSystem::style();
if (strpos($style, '--pcw-default-bg:#123456') === false || strpos($style, '--pcw-default-radius:80px') === false || strpos($style, 'position:fixed') !== false) { throw new RuntimeException('Scoped design defaults incorrect.'); }
echo "PASS: design defaults are bounded, sanitized and scoped\n";
