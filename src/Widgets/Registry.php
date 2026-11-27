<?php
namespace Phehrest\CustomWidgets\Widgets;

if (!defined('ABSPATH')) { exit; }

final class Registry {
    public function manifests(): array {
        $result = [];
        foreach (glob(PCW_PATH . 'widgets/*/manifest.json') ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (!is_array($data) || empty($data['id']) || empty($data['class']) || empty($data['module_class'])) { continue; }
            $data['_dir'] = dirname($file);
            $result[$data['id']] = $data;
        }
        return $result;
    }
    public function enabled(string $id): bool {
        $options = get_option('pcw_enabled_widgets', []);
        return is_array($options) && !empty($options[$id]);
    }
    public function compatible(array $manifest): bool {
        return defined('ELEMENTOR_VERSION') && defined('WC_VERSION') && class_exists('WooCommerce')
            && version_compare(PHP_VERSION, $manifest['minimum_php'], '>=')
            && version_compare(get_bloginfo('version'), $manifest['minimum_wordpress'], '>=')
            && version_compare(ELEMENTOR_VERSION, $manifest['minimum_elementor'], '>=')
            && version_compare(WC_VERSION, $manifest['minimum_woocommerce'], '>=');
    }
    public function boot(): void {
        foreach ($this->manifests() as $manifest) {
            if (!$this->enabled($manifest['id']) || !$this->compatible($manifest)) { continue; }
            $module = $manifest['module_class'];
            if (class_exists($module)) { (new $module())->boot(); }
        }
    }
}
