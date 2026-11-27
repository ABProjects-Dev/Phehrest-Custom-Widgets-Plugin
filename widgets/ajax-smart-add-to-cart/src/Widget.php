<?php
namespace Phehrest\CustomWidgets\Widgets\AjaxSmartAddToCart;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;
use Phehrest\CustomWidgets\DesignSystem;
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
        $this->add_control('product_search', ['type' => Controls_Manager::RAW_HTML, 'raw' => '<div class="pcw-product-search"><label>جست‌وجوی محصول <input class="pcw-product-search__input" type="search" autocomplete="off" placeholder="نام محصول" aria-label="جست‌وجوی محصول"></label><div class="pcw-product-search__results" aria-live="polite"></div></div>', 'condition' => ['source' => 'selected']]);
        $this->add_control('button_text', ['label' => __('متن دکمه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('افزودن به سبد خرید', 'phehrest-custom-widgets')]);
        $this->add_control('loading_text', ['label' => __('متن بارگذاری', 'phehrest-custom-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('در حال به‌روزرسانی…', 'phehrest-custom-widgets')]);
        foreach (['plus' => ['مثبت', 'fas fa-plus'], 'minus' => ['منفی', 'fas fa-minus'], 'remove' => ['حذف', 'fas fa-trash-alt']] as $name => $icon) {
            $this->add_control('icon_' . $name, ['label' => sprintf(__('آیکون %s', 'phehrest-custom-widgets'), $icon[0]), 'type' => Controls_Manager::ICONS, 'default' => ['value' => $icon[1], 'library' => 'fa-solid']]);
        }
        $this->add_control('show_quantity', ['label' => __('نمایش عدد', 'phehrest-custom-widgets'), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('out_of_stock', ['label' => __('محصول ناموجود', 'phehrest-custom-widgets'), 'type' => Controls_Manager::SELECT, 'default' => 'message', 'options' => ['message' => __('نمایش پیام', 'phehrest-custom-widgets'), 'hide' => __('پنهان‌کردن ابزارک', 'phehrest-custom-widgets')]]);
        $this->end_controls_section();
        // Keep existing control IDs where possible, but scope button and counter styles independently.
        $this->start_controls_section('pcw_style_add', ['label' => __('دکمه افزودن', 'phehrest-custom-widgets'), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('add_min_width', [
            'label' => __('حداقل عرض دکمه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'], 'range' => ['px' => ['min' => 0, 'max' => 1000]],
            'selectors' => ['{{WRAPPER}} .pcw-cart' => '--pcw-add-min-width: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_responsive_control('radius', ['label' => __('گردی گوشه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', 'em'], 'range' => ['px' => ['min' => 0, 'max' => 150]], 'selectors' => ['{{WRAPPER}} .pcw-cart' => '--pcw-add-radius: {{SIZE}}{{UNIT}};']]);
        $this->add_responsive_control('inner_padding', ['label' => __('فاصله داخلی دکمه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em', '%'], 'selectors' => ['{{WRAPPER}} .pcw-cart__add' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('add_border_style', ['label' => __('نوع حاشیه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => ['' => __('پیش‌فرض', 'phehrest-custom-widgets'), 'none' => __('بدون حاشیه', 'phehrest-custom-widgets'), 'solid' => __('یکپارچه', 'phehrest-custom-widgets'), 'dashed' => __('خط‌چین', 'phehrest-custom-widgets'), 'dotted' => __('نقطه‌چین', 'phehrest-custom-widgets')], 'selectors' => ['{{WRAPPER}} .pcw-cart__add' => 'border-style: {{VALUE}};']]);
        $this->add_responsive_control('add_border_width', ['label' => __('ضخامت حاشیه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px'], 'selectors' => ['{{WRAPPER}} .pcw-cart__add' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->start_controls_tabs('pcw_add_states');
        $this->start_controls_tab('pcw_add_normal', ['label' => __('عادی', 'phehrest-custom-widgets')]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'typography', 'selector' => '{{WRAPPER}} .pcw-cart__add .pcw-cart__label']);
        $this->add_control('text_color', ['label' => __('رنگ متن', 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart' => '--pcw-text: {{VALUE}};']]);
        $this->add_control('background', ['label' => __('رنگ پس‌زمینه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart' => '--pcw-bg: {{VALUE}};']]);
        $this->add_control('initial_border_color', ['label' => __('رنگ حاشیه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart__add' => 'border-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->start_controls_tab('pcw_add_hover', ['label' => __('هاور', 'phehrest-custom-widgets')]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'hover_typography', 'selector' => '{{WRAPPER}} .pcw-cart__add:hover:not(:disabled) .pcw-cart__label']);
        $this->add_control('hover_text_color', ['label' => __('رنگ متن', 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart__add:hover:not(:disabled)' => 'color: {{VALUE}};']]);
        $this->add_control('hover_color', ['label' => __('رنگ پس‌زمینه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart' => '--pcw-hover: {{VALUE}};']]);
        $this->add_control('hover_border_color', ['label' => __('رنگ حاشیه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart__add:hover:not(:disabled)' => 'border-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->start_controls_section('pcw_style_counter', ['label' => __('شمارنده', 'phehrest-custom-widgets'), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('counter_container_heading', ['label' => __('ظاهر مشترک شمارنده', 'phehrest-custom-widgets'), 'type' => Controls_Manager::HEADING]);
        foreach (['counter_background' => ['رنگ پس‌زمینه', '--pcw-counter-bg'], 'border_color' => ['رنگ حاشیه', '--pcw-border']] as $name => $value) {
            $this->add_control($name, ['label' => __($value[0], 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart' => $value[1] . ': {{VALUE}};']]);
        }
        foreach (['counter_radius' => ['گردی گوشه شمارنده', '--pcw-counter-radius'], 'counter_gap' => ['فاصله دکمه‌ها و عدد', '--pcw-counter-gap'], 'counter_icon_size' => ['اندازه آیکون دکمه‌ها', '--pcw-counter-icon-size']] as $name => $value) {
            $this->add_responsive_control($name, ['label' => __($value[0], 'phehrest-custom-widgets'), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', 'em'], 'range' => ['px' => ['min' => 0, 'max' => 150]], 'selectors' => ['{{WRAPPER}} .pcw-cart' => $value[1] . ': {{SIZE}}{{UNIT}};']]);
        }
        $this->add_responsive_control('counter_padding', ['label' => __('فاصله داخلی شمارنده', 'phehrest-custom-widgets'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em', '%'], 'selectors' => ['{{WRAPPER}} .pcw-cart__counter' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('step_heading', ['label' => __('دکمه‌های آیکونی', 'phehrest-custom-widgets'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_responsive_control('step_padding', ['label' => __('فاصله داخلی دکمه آیکون', 'phehrest-custom-widgets'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .pcw-cart__step' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_responsive_control('step_radius', ['label' => __('گردی دکمه آیکون', 'phehrest-custom-widgets'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .pcw-cart__step' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('step_border_style', ['label' => __('نوع حاشیه دکمه آیکون', 'phehrest-custom-widgets'), 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => ['' => __('پیش‌فرض', 'phehrest-custom-widgets'), 'none' => __('بدون حاشیه', 'phehrest-custom-widgets'), 'solid' => __('یکپارچه', 'phehrest-custom-widgets'), 'dashed' => __('خط‌چین', 'phehrest-custom-widgets'), 'dotted' => __('نقطه‌چین', 'phehrest-custom-widgets')], 'selectors' => ['{{WRAPPER}} .pcw-cart__step' => 'border-style: {{VALUE}};']]);
        $this->add_responsive_control('step_border_width', ['label' => __('ضخامت حاشیه دکمه آیکون', 'phehrest-custom-widgets'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px'], 'selectors' => ['{{WRAPPER}} .pcw-cart__step' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->start_controls_tabs('pcw_step_states');
        $this->start_controls_tab('pcw_step_normal', ['label' => __('عادی', 'phehrest-custom-widgets')]);
        foreach (['plus_color' => ['رنگ آیکون مثبت', '--pcw-plus'], 'minus_color' => ['رنگ آیکون منفی', '--pcw-minus'], 'remove_color' => ['رنگ آیکون حذف', '--pcw-remove']] as $name => $value) {
            $this->add_control($name, ['label' => __($value[0], 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart' => $value[1] . ': {{VALUE}};']]);
        }
        $this->add_control('step_background', ['label' => __('پس‌زمینه دکمه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart__step' => 'background-color: {{VALUE}};']]);
        $this->add_control('step_border_color', ['label' => __('رنگ حاشیه دکمه', 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart__step' => 'border-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->start_controls_tab('pcw_step_hover', ['label' => __('هاور', 'phehrest-custom-widgets')]);
        foreach (['step_hover_color' => ['رنگ آیکون', 'color'], 'step_hover_background' => ['پس‌زمینه دکمه', 'background-color'], 'step_hover_border_color' => ['رنگ حاشیه دکمه', 'border-color']] as $name => $value) {
            $this->add_control($name, ['label' => __($value[0], 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart__step:hover:not(:disabled)' => $value[1] . ': {{VALUE}};']]);
        }
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_control('quantity_heading', ['label' => __('متن تعداد', 'phehrest-custom-widgets'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'counter_typography', 'selector' => '{{WRAPPER}} .pcw-cart__quantity']);
        $this->add_control('quantity_color', ['label' => __('رنگ عدد', 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart' => '--pcw-quantity-color: {{VALUE}};']]);
        $this->add_control('quantity_background', ['label' => __('پس‌زمینه عدد', 'phehrest-custom-widgets'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .pcw-cart__quantity' => 'background-color: {{VALUE}};']]);
        $this->add_responsive_control('quantity_padding', ['label' => __('فاصله داخلی عدد', 'phehrest-custom-widgets'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .pcw-cart__quantity' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_responsive_control('quantity_radius', ['label' => __('گردی گوشه عدد', 'phehrest-custom-widgets'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .pcw-cart__quantity' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
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
        $classes = 'pcw-cart' . (($settings['show_quantity'] ?? '') === 'yes' ? '' : ' pcw-cart--hide-quantity');
        ?>
        <div class="pcw-design" style="<?php echo esc_attr(DesignSystem::style()); ?>"><div class="<?php echo esc_attr($classes); ?>" data-product-id="<?php echo esc_attr($id); ?>" data-variable="<?php echo $product->is_type('variable') ? '1' : '0'; ?>" data-loading="<?php echo esc_attr($settings['loading_text']); ?>" data-out-of-stock="<?php echo esc_attr($settings['out_of_stock']); ?>" aria-busy="false">
            <div class="pcw-cart__control" hidden>
                <button type="button" class="pcw-cart__add" data-operation="add"><span class="pcw-cart__label" dir="auto"><?php echo esc_html($settings['button_text']); ?></span></button>
                <div class="pcw-cart__counter" hidden><button type="button" class="pcw-cart__step pcw-cart__down" data-operation="decrease" aria-label="<?php echo esc_attr__('کاهش تعداد', 'phehrest-custom-widgets'); ?>"><span class="pcw-cart__minus" aria-hidden="true"><?php Icons_Manager::render_icon($settings['icon_minus'], ['aria-hidden' => 'true']); ?></span><span class="pcw-cart__remove" aria-hidden="true"><?php Icons_Manager::render_icon($settings['icon_remove'], ['aria-hidden' => 'true']); ?></span></button><span class="pcw-cart__quantity" aria-live="polite">0</span><button type="button" class="pcw-cart__step pcw-cart__up" data-operation="increase" aria-label="<?php echo esc_attr__('افزایش تعداد', 'phehrest-custom-widgets'); ?>"><span aria-hidden="true"><?php Icons_Manager::render_icon($settings['icon_plus'], ['aria-hidden' => 'true']); ?></span></button></div>
            </div>
            <p class="pcw-cart__status" role="status" aria-live="polite"></p>
            <button type="button" class="pcw-cart__retry" hidden><?php echo esc_html__('تلاش مجدد', 'phehrest-custom-widgets'); ?></button>
            <noscript><a href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html__('برای خرید به صفحه محصول بروید.', 'phehrest-custom-widgets'); ?></a></noscript>
        </div></div><?php
    }
}
