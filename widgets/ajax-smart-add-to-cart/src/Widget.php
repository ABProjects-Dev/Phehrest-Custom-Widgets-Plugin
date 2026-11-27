<?php
namespace Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;
if (!defined('ABSPATH')) { exit; }
final class Widget extends Widget_Base {
    public function get_name() { return 'phehrest_ajax_smart_add_to_cart'; }
    public function get_title() { return __('دکمه هوشمند افزودن به سبد خرید', 'phehrest-custom-widgets'); }
    public function get_icon() { return 'eicon-cart'; }
    public function get_categories() { return ['woocommerce-elements']; }
    public function get_style_depends() { return ['pcw-smart-cart']; }
    public function get_script_depends() { return ['pcw-smart-cart']; }
    protected function register_controls() {
        $this->start_controls_section('pcw_content', ['label' => __('محتوا', 'phehrest-custom-widgets')]);
        $this->add_control('source', ['label' => __('منبع محصول', 'phehrest-custom-widgets'), 'type' => Controls_Manager::SELECT, 'default' => 'current', 'options' => ['current' => __('محصول جاری / Loop', 'phehrest-custom-widgets'), 'selected' => __('محصول مشخص', 'phehrest-custom-widgets')]]);
        $this->add_control('product_id', ['label' => __('شناسه محصول', 'phehrest-custom-widgets'), 'type' => Controls_Manager::NUMBER, 'min' => 1, 'condition' => ['source' => 'selected'], 'description' => __('شناسه عددی محصول را وارد کنید.', 'phehrest-custom-widgets')]);
        $this->add_control('button_text', ['label' => __('متن دکمه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('افزودن به سبد خرید', 'phehrest-custom-widgets')]);
        $this->add_control('loading_text', ['label' => __('متن بارگذاری', 'phehrest-custom-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('در حال به‌روزرسانی…', 'phehrest-custom-widgets')]);
        foreach (['add' => ['افزودن', 'fas fa-shopping-bag'], 'plus' => ['مثبت', 'fas fa-plus'], 'minus' => ['منفی', 'fas fa-minus'], 'remove' => ['حذف', 'fas fa-trash-alt']] as $name => $icon) {
            $this->add_control('icon_' . $name, ['label' => sprintf(__('آیکون %s', 'phehrest-custom-widgets'), $icon[0]), 'type' => Controls_Manager::ICONS, 'default' => ['value' => $icon[1], 'library' => 'fa-solid']]);
        }
        $this->add_control('animate', ['label' => __('انیمیشن', 'phehrest-custom-widgets'), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_quantity', ['label' => __('نمایش عدد', 'phehrest-custom-widgets'), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('out_of_stock', ['label' => __('محصول ناموجود', 'phehrest-custom-widgets'), 'type' => Controls_Manager::SELECT, 'default' => 'message', 'options' => ['message' => __('نمایش پیام', 'phehrest-custom-widgets'), 'hide' => __('پنهان‌کردن ابزارک', 'phehrest-custom-widgets')]]);
        $this->end_controls_section();
        $this->start_controls_section('pcw_style', ['label' => __('ظاهر', 'phehrest-custom-widgets'), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'typography', 'selector' => '{{WRAPPER}} .pcw-cart']);
        foreach (['text_color' => ['رنگ متن', '--pcw-text'], 'background' => ['رنگ دکمه اولیه', '--pcw-bg'], 'counter_background' => ['رنگ شمارنده', '--pcw-counter-bg'], 'border_color' => ['رنگ حاشیه', '--pcw-border'], 'hover_color' => ['رنگ Hover', '--pcw-hover'], 'plus_color' => ['رنگ مثبت', '--pcw-plus'], 'minus_color' => ['رنگ منفی', '--pcw-minus'], 'remove_color' => ['رنگ حذف', '--pcw-remove']] as $name => $value) {
            $this->add_control($name, ['label' => $value[0], 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart' => $value[1] . ': {{VALUE}};']]);
        }
        foreach (['radius' => ['گردی گوشه', '--pcw-radius'], 'icon_size' => ['اندازه آیکون', '--pcw-icon-size'], 'gap' => ['فاصله آیکون و عدد', '--pcw-gap'], 'height' => ['ارتفاع', '--pcw-height'], 'width' => ['عرض', '--pcw-width']] as $name => $value) {
            $this->add_responsive_control($name, ['label' => $value[0], 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', '%', 'em'], 'range' => ['px' => ['min' => 0, 'max' => 600]], 'selectors' => ['{{WRAPPER}} .pcw-cart' => $value[1] . ': {{SIZE}}{{UNIT}};']]);
        }
        $this->add_responsive_control('inner_padding', ['label' => __('فاصله داخلی', 'phehrest-custom-widgets'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em', '%'], 'selectors' => ['{{WRAPPER}} .pcw-cart__add, {{WRAPPER}} .pcw-cart__counter' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name' => 'shadow', 'selector' => '{{WRAPPER}} .pcw-cart__add, {{WRAPPER}} .pcw-cart__counter']);
        $this->add_control('transition', ['label' => __('زمان transition (ms)', 'phehrest-custom-widgets'), 'type' => Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 1000]], 'selectors' => ['{{WRAPPER}} .pcw-cart' => '--pcw-duration: {{SIZE}}ms;']]);
        $this->end_controls_section();
    }
    private function product_id(array $settings): int {
        if (($settings['source'] ?? 'current') === 'selected') { return absint($settings['product_id'] ?? 0); }
        $id = get_the_ID();
        return $id && get_post_type($id) === 'product' ? (int) $id : 0;
    }
    protected function render() {
        $settings = $this->get_settings_for_display();
        $id = $this->product_id($settings);
        $product = $id ? wc_get_product($id) : false;
        if (!$product) { echo '<p class="pcw-cart__notice">' . esc_html__('محصول معتبری در این موقعیت پیدا نشد.', 'phehrest-custom-widgets') . '</p>'; return; }
        if (!$product->is_type(['simple', 'variable'])) { echo '<p class="pcw-cart__notice">' . esc_html__('برای خرید این محصول از صفحه محصول استفاده کنید.', 'phehrest-custom-widgets') . ' <a href="' . esc_url(get_permalink($id)) . '">' . esc_html__('مشاهده محصول', 'phehrest-custom-widgets') . '</a></p>'; return; }
        if (!$product->is_in_stock() && ($settings['out_of_stock'] ?? '') === 'hide') { return; }
        $classes = 'pcw-cart' . (($settings['animate'] ?? '') === 'yes' ? '' : ' pcw-cart--no-animation') . (($settings['show_quantity'] ?? '') === 'yes' ? '' : ' pcw-cart--hide-quantity');
        ?>
        <div class="<?php echo esc_attr($classes); ?>" data-product-id="<?php echo esc_attr($id); ?>" data-variable="<?php echo $product->is_type('variable') ? '1' : '0'; ?>" data-loading="<?php echo esc_attr($settings['loading_text']); ?>" data-out-of-stock="<?php echo esc_attr($settings['out_of_stock']); ?>" aria-busy="false">
            <div class="pcw-cart__control" hidden>
                <button type="button" class="pcw-cart__add" data-operation="add"><span class="pcw-cart__icon" aria-hidden="true"><?php Icons_Manager::render_icon($settings['icon_add'], ['aria-hidden' => 'true']); ?></span><span><?php echo esc_html($settings['button_text']); ?></span></button>
                <div class="pcw-cart__counter" hidden><button type="button" class="pcw-cart__step pcw-cart__down" data-operation="decrease" aria-label="<?php echo esc_attr__('کاهش تعداد', 'phehrest-custom-widgets'); ?>"><span class="pcw-cart__minus" aria-hidden="true"><?php Icons_Manager::render_icon($settings['icon_minus'], ['aria-hidden' => 'true']); ?></span><span class="pcw-cart__remove" aria-hidden="true"><?php Icons_Manager::render_icon($settings['icon_remove'], ['aria-hidden' => 'true']); ?></span></button><span class="pcw-cart__quantity" aria-live="polite"></span><button type="button" class="pcw-cart__step pcw-cart__up" data-operation="increase" aria-label="<?php echo esc_attr__('افزایش تعداد', 'phehrest-custom-widgets'); ?>"><span aria-hidden="true"><?php Icons_Manager::render_icon($settings['icon_plus'], ['aria-hidden' => 'true']); ?></span></button></div>
            </div>
            <p class="pcw-cart__status" role="status" aria-live="polite"></p>
            <button type="button" class="pcw-cart__retry" hidden><?php echo esc_html__('تلاش مجدد', 'phehrest-custom-widgets'); ?></button>
            <noscript><a href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html__('برای خرید به صفحه محصول بروید.', 'phehrest-custom-widgets'); ?></a></noscript>
        </div><?php
    }
}
