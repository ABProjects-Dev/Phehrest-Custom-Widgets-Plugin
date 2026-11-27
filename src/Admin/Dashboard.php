<?php
namespace Phehrest\CustomWidgets\Admin;
use Phehrest\CustomWidgets\Widgets\Registry;
if (!defined('ABSPATH')) { exit; }
final class Dashboard {
    private Registry $registry;
    public function __construct(Registry $registry) { $this->registry = $registry; }
    public function boot(): void {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_pcw_save_widgets', [$this, 'save']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
    }
    public function menu(): void {
        add_menu_page('ابزارک‌های فهرست', 'ابزارک‌های فهرست', 'manage_options', 'pcw-widgets', [$this, 'render'], 'dashicons-grid-view');
    }
    public function assets(string $hook): void {
        if ($hook !== 'toplevel_page_pcw-widgets') { return; }
        wp_enqueue_style('pcw-admin', PCW_URL . 'assets/admin/dashboard.css', [], PCW_VERSION);
        wp_enqueue_script('pcw-admin', PCW_URL . 'assets/admin/dashboard.js', [], PCW_VERSION, true);
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
    public function render(): void {
        if (!current_user_can('manage_options')) { return; }
        ?>
        <div class="wrap pcw-admin" dir="rtl"><header><p class="pcw-admin__eyebrow">PHEHREST CUSTOM WIDGETS</p><h1>ابزارک‌های فهرست</h1><p>مدیریت مستقل ابزارک‌ها؛ هر ابزارک جدید به‌صورت پیش‌فرض غیرفعال است.</p></header>
        <?php if (isset($_GET['updated'])): ?><div class="notice notice-success"><p>تنظیمات ذخیره شد.</p></div><?php endif; ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="pcw_save_widgets"><?php wp_nonce_field('pcw_save_widgets'); ?>
            <div class="pcw-carousel" aria-label="<?php echo esc_attr__('فهرست ابزارک‌ها', 'phehrest-custom-widgets'); ?>">
                <div class="pcw-carousel__navigation" hidden>
                    <button type="button" class="button pcw-carousel__previous" aria-label="<?php echo esc_attr__('ابزارک قبلی', 'phehrest-custom-widgets'); ?>">‹</button>
                    <button type="button" class="button pcw-carousel__next" aria-label="<?php echo esc_attr__('ابزارک بعدی', 'phehrest-custom-widgets'); ?>">›</button>
                </div>
                <div class="pcw-carousel__track" tabindex="0" aria-label="<?php echo esc_attr__('کارت‌های ابزارک؛ برای دیدن جزئیات نام ابزارک را باز کنید', 'phehrest-custom-widgets'); ?>">
                <?php foreach ($this->registry->manifests() as $id => $m): $compatible = $this->registry->compatible($m); ?>
                    <article class="pcw-card">
                        <details class="pcw-card__details">
                            <summary><span class="pcw-card__title"><?php echo esc_html($m['name_fa']); ?></span></summary>
                            <div class="pcw-card__body">
                                <span class="pcw-card__badge"><?php echo esc_html($m['status']); ?></span>
                                <p><?php echo esc_html($m['description_fa']); ?></p>
                                <dl><div><dt>نسخه</dt><dd><?php echo esc_html($m['version']); ?></dd></div><div><dt>ایجاد</dt><dd><?php echo esc_html($m['created']); ?></dd></div><div><dt>به‌روزرسانی</dt><dd><?php echo esc_html($m['updated']); ?></dd></div><div><dt>وابستگی و سازگاری</dt><dd><?php echo $compatible ? esc_html__('شرایط نسخه برقرار است؛ تست سازگاری کامل نشده', 'phehrest-custom-widgets') : esc_html__('وابستگی یا نسخه موردنیاز موجود نیست', 'phehrest-custom-widgets'); ?></dd></div></dl>
                                <p class="pcw-card__requirements"><?php echo esc_html(sprintf('PHP %s+ · WordPress %s+ · Elementor %s+ · WooCommerce %s+', $m['minimum_php'], $m['minimum_wordpress'], $m['minimum_elementor'], $m['minimum_woocommerce'])); ?></p>
                            </div>
                        </details>
                        <label class="pcw-card__toggle"><input type="checkbox" role="switch" name="widgets[<?php echo esc_attr($id); ?>]" value="1" aria-label="<?php echo esc_attr(sprintf(__('فعال‌سازی %s', 'phehrest-custom-widgets'), $m['name_fa'])); ?>" <?php checked($this->registry->enabled($id)); ?> <?php disabled(!$compatible); ?>><span class="pcw-card__on">فعال</span><span class="pcw-card__off">غیرفعال</span></label>
                    </article>
                <?php endforeach; ?>
                </div>
            </div>
            <?php submit_button('ذخیره تنظیمات'); ?>
        </form></div><?php
    }
}
