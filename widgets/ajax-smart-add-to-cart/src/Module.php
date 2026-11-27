<?php
namespace Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart;
if (!defined('ABSPATH')) { exit; }
final class Module {
    public function boot(): void {
        add_action('elementor/widgets/register', [$this, 'register_widget']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('elementor/editor/after_enqueue_scripts', [$this, 'register_assets']);
        foreach (['wp_ajax_pcw_cart_state', 'wp_ajax_nopriv_pcw_cart_state'] as $hook) { add_action($hook, [new AjaxController(), 'state']); }
        foreach (['wp_ajax_pcw_cart_change', 'wp_ajax_nopriv_pcw_cart_change'] as $hook) { add_action($hook, [new AjaxController(), 'change']); }
    }
    public function register_widget($manager): void { $manager->register(new Widget()); }
    public function register_assets(): void {
        $url = PCW_URL . 'widgets/ajax-smart-add-to-cart/assets/';
        wp_register_style('pcw-smart-cart', $url . 'css/frontend.css', [], PCW_VERSION);
        wp_register_script('pcw-smart-cart', $url . 'js/frontend.js', [], PCW_VERSION, true);
        wp_localize_script('pcw-smart-cart', 'pcwSmartCart', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'messages' => [
                'network' => __('ارتباط با سرور برقرار نشد. وضعیت سبد را بررسی و دوباره تلاش کنید.', 'phehrest-custom-widgets'),
                'choose' => __('ابتدا ویژگی‌های محصول را انتخاب کنید.', 'phehrest-custom-widgets'),
                'increase' => __('افزایش تعداد', 'phehrest-custom-widgets'),
                'decrease' => __('کاهش تعداد', 'phehrest-custom-widgets'),
                'remove' => __('حذف از سبد خرید', 'phehrest-custom-widgets'),
                'quantity' => __('تعداد در سبد خرید:', 'phehrest-custom-widgets'),
            ],
        ]);
    }
}
