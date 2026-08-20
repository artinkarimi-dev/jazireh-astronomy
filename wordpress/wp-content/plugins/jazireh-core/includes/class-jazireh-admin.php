<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Admin
{
    public static function boot()
    {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_head', array(__CLASS__, 'styles'));
        add_filter('admin_footer_text', array(__CLASS__, 'footer_text'));
        add_action('wp_dashboard_setup', array(__CLASS__, 'dashboard'));
    }

    public static function menu()
    {
        add_menu_page('جزیره', 'جزیره', 'edit_posts', 'jazireh-dashboard', array(__CLASS__, 'render_dashboard'), 'dashicons-star-filled', 2);
        add_submenu_page('jazireh-dashboard', 'داشبورد جزیره', 'داشبورد', 'edit_posts', 'jazireh-dashboard', array(__CLASS__, 'render_dashboard'));
        add_submenu_page('jazireh-dashboard', 'همه اخبار', 'همه اخبار', 'edit_posts', 'edit.php?post_type=' . Jazireh_News::POST_TYPE);
        add_submenu_page('jazireh-dashboard', 'افزودن خبر', 'افزودن خبر', 'edit_posts', 'post-new.php?post_type=' . Jazireh_News::POST_TYPE);
        add_submenu_page('jazireh-dashboard', 'جزیره دیلی', 'جزیره دیلی', 'edit_posts', 'edit.php?post_type=' . Jazireh_Daily::POST_TYPE);
        add_submenu_page('jazireh-dashboard', 'افزودن جزیره دیلی', 'افزودن جزیره دیلی', 'edit_posts', 'post-new.php?post_type=' . Jazireh_Daily::POST_TYPE);
    }

    public static function render_dashboard()
    {
        $counts = wp_count_posts(Jazireh_News::POST_TYPE);
        $published = isset($counts->publish) ? (int) $counts->publish : 0;
        $draft = isset($counts->draft) ? (int) $counts->draft : 0;
        ?>
        <div class="wrap jazireh-admin-wrap" dir="rtl">
            <div class="jazireh-admin-hero">
                <span>پنل مدیریت اختصاصی</span>
                <h1>جزیره نجوم</h1>
                <p>خبرهای علمی سایت را از این بخش مدیریت کنید. هر تغییری بعد از انتشار، مستقیماً در رابط React نمایش داده می‌شود.</p>
                <div class="jazireh-admin-actions">
                    <a class="button button-primary button-hero" href="<?php echo esc_url(admin_url('post-new.php?post_type=' . Jazireh_News::POST_TYPE)); ?>">افزودن خبر جدید</a>
                    <a class="button button-hero" href="<?php echo esc_url(admin_url('edit.php?post_type=' . Jazireh_News::POST_TYPE)); ?>">مدیریت اخبار</a>
                </div>
            </div>
            <div class="jazireh-admin-grid">
                <div class="jazireh-stat"><strong><?php echo esc_html($published); ?></strong><span>خبر منتشرشده</span></div>
                <div class="jazireh-stat"><strong><?php echo esc_html($draft); ?></strong><span>پیش‌نویس</span></div>
                <div class="jazireh-stat"><strong>REST API</strong><span>اتصال فعال به React</span></div>
            </div>
            <div class="jazireh-help">
                <h2>روش ثبت خبر</h2>
                <ol><li>عنوان خبر را بنویسید.</li><li>خلاصه و متن کامل را وارد کنید.</li><li>دسته‌بندی و تصویر شاخص را انتخاب کنید.</li><li>زمان مطالعه و حالت خبر ویژه را تنظیم کنید.</li><li>روی انتشار بزنید.</li></ol>
            </div>
        </div>
        <?php
    }

    public static function styles()
    {
        ?>
        <style>
            .jazireh-admin-wrap{max-width:1180px;margin-top:24px}.jazireh-admin-hero{border-radius:24px;padding:38px;background:radial-gradient(circle at 10% 10%,rgba(245,158,11,.25),transparent 38%),linear-gradient(135deg,#090f1f,#151b2e);color:#fff;box-shadow:0 18px 48px rgba(15,23,42,.22)}.jazireh-admin-hero span{color:#fbbf24;font-weight:700}.jazireh-admin-hero h1{font-size:34px;margin:10px 0;color:#fff}.jazireh-admin-hero p{font-size:15px;line-height:2;max-width:720px;color:#cbd5e1}.jazireh-admin-actions{display:flex;gap:10px;margin-top:20px}.jazireh-admin-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:18px}.jazireh-stat,.jazireh-help{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:24px;box-shadow:0 8px 25px rgba(15,23,42,.05)}.jazireh-stat strong{display:block;font-size:26px;color:#111827;margin-bottom:8px}.jazireh-stat span{color:#64748b}.jazireh-help{margin-top:18px}.jazireh-help ol{line-height:2.2}.post-type-jazireh_news #postexcerpt .inside p{display:none}@media(max-width:782px){.jazireh-admin-grid{grid-template-columns:1fr}.jazireh-admin-actions{flex-direction:column}.jazireh-admin-hero{padding:24px}}
        </style>
        <?php
    }

    public static function footer_text()
    {
        return 'پنل مدیریت اختصاصی جزیره نجوم';
    }

    public static function dashboard()
    {
        remove_meta_box('dashboard_primary', 'dashboard', 'side');
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    }
}
