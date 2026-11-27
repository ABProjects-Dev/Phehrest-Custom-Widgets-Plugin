<?php
namespace Phehrest\CustomWidgets\Admin;
use Phehrest\CustomWidgets\Widgets\Registry;
use Phehrest\CustomWidgets\DesignSystem;
if (!defined('ABSPATH')) { exit; }
final class Dashboard {
    private Registry $registry;
    public function __construct(Registry $registry) { $this->registry = $registry; }
    public function boot(): void {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_pcw_save_widgets', [$this, 'save']);
        add_action('admin_post_pcw_save_design', [$this, 'save_design']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
    }
    public function menu(): void {
        add_menu_page('ابزارک‌های فهرست', 'ابزارک‌های فهرست', 'manage_options', 'pcw-widgets', [$this, 'render'], 'dashicons-grid-view');
    }
    public function assets(string $hook): void {
        if ($hook === 'toplevel_page_pcw-widgets') { wp_enqueue_style('pcw-admin', PCW_URL . 'assets/admin/dashboard.css', [], PCW_VERSION); }
    }
    public function save(): void {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('دسترسی غیرمجاز.', 'phehrest-custom-widgets'), '', ['response' => 403]); }
        check_admin_referer('pcw_save_widgets');
        $selected = isset($_POST['widgets']) && is_array($_POST['widgets']) ? wp_unslash($_POST['widgets']) : [];
        $enabled = [];
        foreach ($this->registry->manifests() as $id => $manifest) {
            if (isset($selected[$id]) && $this->registry->compatible($manifest)) { $enabled[$id] = true; }
        }
        update_option('pcw_enabled_widgets', $enabled, false);
        wp_safe_redirect(add_query_arg(['page' => 'pcw-widgets', 'updated' => '1'], admin_url('admin.php')));
        exit;
    }
    public function save_design(): void {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('دسترسی غیرمجاز.', 'phehrest-custom-widgets'), '', ['response' => 403]); }
        check_admin_referer('pcw_save_design');
        $values = isset($_POST['design']) && is_array($_POST['design']) ? wp_unslash($_POST['design']) : [];
        update_option('pcw_design_system', DesignSystem::sanitize($values), false);
        wp_safe_redirect(add_query_arg(['page' => 'pcw-widgets', 'updated' => '1'], admin_url('admin.php')));
        exit;
    }
    public function render(): void {
        if (!current_user_can('manage_options')) { return; }
        ?>
        <div class="wrap pcw-admin" dir="rtl"><header><p class="pcw-admin__eyebrow">PHEHREST CUSTOM WIDGETS</p><h1>ابزارک‌های فهرست</h1><p>مدیریت مستقل ابزارک‌ها؛ هر ابزارک جدید به‌صورت پیش‌فرض غیرفعال است.</p></header>
        <?php if (isset($_GET['updated'])): ?><div class="notice notice-success"><p>تنظیمات ذخیره شد.</p></div><?php endif; ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="pcw_save_widgets"><?php wp_nonce_field('pcw_save_widgets'); ?>
            <div class="pcw-admin__grid">
            <?php foreach ($this->registry->manifests() as $id => $m): $compatible = $this->registry->compatible($m); ?>
                <article class="pcw-card"><div class="pcw-card__top"><span class="dashicons dashicons-cart pcw-card__icon" aria-hidden="true"></span><span class="pcw-card__badge"><?php echo esc_html($m['status']); ?></span></div>
                <h2><?php echo esc_html($m['name_fa']); ?></h2><p><?php echo esc_html($m['description_fa']); ?></p>
                <dl><div><dt>نسخه</dt><dd><?php echo esc_html($m['version']); ?></dd></div><div><dt>ایجاد</dt><dd><?php echo esc_html($m['created']); ?></dd></div><div><dt>به‌روزرسانی</dt><dd><?php echo esc_html($m['updated']); ?></dd></div><div><dt>وابستگی و سازگاری</dt><dd><?php echo $compatible ? 'شرایط نسخه برقرار است؛ تست سازگاری کامل نشده' : 'وابستگی یا نسخه موردنیاز موجود نیست'; ?></dd></div></dl>
                <p class="pcw-card__requirements"><?php echo esc_html(sprintf('PHP %s+ · WordPress %s+ · Elementor %s+ · WooCommerce %s+', $m['minimum_php'], $m['minimum_wordpress'], $m['minimum_elementor'], $m['minimum_woocommerce'])); ?></p>
                <label class="pcw-card__toggle"><input type="checkbox" name="widgets[<?php echo esc_attr($id); ?>]" value="1" <?php checked($this->registry->enabled($id)); ?> <?php disabled(!$compatible); ?>> فعال‌سازی ابزارک</label>
                </article>
            <?php endforeach; ?></div>
            <?php submit_button('ذخیره تنظیمات'); ?>
        </form>
        <?php $design = DesignSystem::sanitize(get_option('pcw_design_system', [])); ?>
        <section class="pcw-card pcw-admin__design"><h2>طراحی مشترک</h2><p>این پیش‌فرض‌ها فقط در محدوده ابزارک‌های افزونه اعمال می‌شوند؛ تنظیمات همان ابزارک در المنتور اولویت دارند.</p>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post"><input type="hidden" name="action" value="pcw_save_design"><?php wp_nonce_field('pcw_save_design'); ?>
        <div class="pcw-admin__fields">
        <?php foreach (['primary'=>'رنگ اصلی', 'text'=>'متن دکمه', 'surface'=>'پس‌زمینه شمارنده', 'border'=>'حاشیه', 'positive'=>'مثبت', 'negative'=>'منفی', 'danger'=>'حذف'] as $key => $label): ?>
            <label><?php echo esc_html($label); ?><input type="text" name="design[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($design[$key] ?? ''); ?>" placeholder="#5060db" pattern="#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?" dir="ltr"></label>
        <?php endforeach; ?>
        <?php foreach (['radius'=>'گردی (px)', 'gap'=>'فاصله (px)', 'duration'=>'زمان انیمیشن (ms)'] as $key => $label): ?>
            <label><?php echo esc_html($label); ?><input type="number" min="0" max="<?php echo $key === 'duration' ? '1000' : '80'; ?>" name="design[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($design[$key] ?? ''); ?>"></label>
        <?php endforeach; ?>
        <label>سایه<select name="design[shadow]"><?php foreach ([''=>'پیش‌فرض','none'=>'بدون سایه','soft'=>'ملایم','medium'=>'متوسط'] as $key => $label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($design['shadow'] ?? '', $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
        <label>فونت<select name="design[font]"><?php foreach ([''=>'پیش‌فرض','inherit'=>'قالب','tahoma'=>'Tahoma','system'=>'سیستم'] as $key => $label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($design['font'] ?? '', $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
        </div><?php submit_button('ذخیره طراحی'); ?></form></section></div><?php
    }
}
