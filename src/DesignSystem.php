<?php
namespace Phehrest\CustomWidgets;
if (!defined('ABSPATH')) { exit; }
/** Shared defaults are inherited only by the plugin's own widget wrapper. */
final class DesignSystem {
    public static function sanitize($input): array {
        $input = is_array($input) ? $input : [];
        $values = [];
        foreach (['primary', 'text', 'surface', 'border', 'positive', 'negative', 'danger'] as $name) {
            $color = isset($input[$name]) && is_string($input[$name]) ? sanitize_hex_color($input[$name]) : null;
            if ($color) { $values[$name] = $color; }
        }
        foreach (['radius' => 80, 'gap' => 80, 'duration' => 1000] as $name => $max) {
            if (isset($input[$name]) && is_scalar($input[$name]) && $input[$name] !== '') { $values[$name] = min($max, max(0, (int) $input[$name])); }
        }
        if (isset($input['shadow']) && in_array($input['shadow'], ['none', 'soft', 'medium'], true)) { $values['shadow'] = $input['shadow']; }
        if (isset($input['font']) && in_array($input['font'], ['inherit', 'tahoma', 'system'], true)) { $values['font'] = $input['font']; }
        return $values;
    }
    public static function style(): string {
        $values = self::sanitize(get_option('pcw_design_system', []));
        $mapping = ['primary' => 'bg', 'text' => 'text', 'surface' => 'counter-bg', 'border' => 'border', 'positive' => 'plus', 'negative' => 'minus', 'danger' => 'remove'];
        $css = [];
        foreach ($mapping as $key => $variable) { if (isset($values[$key])) { $css[] = '--pcw-default-' . $variable . ':' . $values[$key]; } }
        foreach (['radius' => 'px', 'gap' => 'px', 'duration' => 'ms'] as $key => $unit) { if (isset($values[$key])) { $css[] = '--pcw-default-' . $key . ':' . $values[$key] . $unit; } }
        $shadows = ['none' => 'none', 'soft' => '0 5px 16px rgba(34,49,106,.08)', 'medium' => '0 10px 24px rgba(34,49,106,.18)'];
        $fonts = ['inherit' => 'inherit', 'tahoma' => 'Tahoma,Arial,sans-serif', 'system' => 'system-ui,sans-serif'];
        if (isset($values['shadow'])) { $css[] = '--pcw-default-shadow:' . $shadows[$values['shadow']]; }
        if (isset($values['font'])) { $css[] = '--pcw-default-font:' . $fonts[$values['font']]; }
        return implode(';', $css);
    }
}
