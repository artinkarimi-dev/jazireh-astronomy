<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Settings
{
    const OPTION_NAME = 'jazireh_settings';
    const WIDGET_PREWARM_HOOK = 'jazireh_prewarm_observatory_widgets';

    public static function boot()
    {
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_post_jazireh_refresh_widget', array(__CLASS__, 'handle_widget_refresh'));
        add_filter('cron_schedules', array(__CLASS__, 'cron_schedules'));
        add_action(self::WIDGET_PREWARM_HOOK, array(__CLASS__, 'prewarm_widgets'));
        add_action('init', array(__CLASS__, 'maybe_schedule_widget_prewarm'));
    }

    public static function cron_schedules($schedules)
    {
        if (!isset($schedules['jazireh_every_30_minutes'])) {
            $schedules['jazireh_every_30_minutes'] = array(
                'interval' => 30 * MINUTE_IN_SECONDS,
                'display' => __('Every 30 minutes', 'jazireh-core'),
            );
        }
        return $schedules;
    }

    public static function schedule_widget_prewarm()
    {
        add_filter('cron_schedules', array(__CLASS__, 'cron_schedules'));
        if (!wp_next_scheduled(self::WIDGET_PREWARM_HOOK)) {
            wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, 'jazireh_every_30_minutes', self::WIDGET_PREWARM_HOOK);
        }
    }

    public static function maybe_schedule_widget_prewarm()
    {
        self::schedule_widget_prewarm();
    }

    public static function clear_widget_prewarm()
    {
        wp_clear_scheduled_hook(self::WIDGET_PREWARM_HOOK);
    }

    public static function register_settings()
    {
        register_setting('jazireh_settings_group', self::OPTION_NAME, array(
            'type' => 'array',
            'sanitize_callback' => array(__CLASS__, 'sanitize_settings'),
            'default' => self::defaults(),
        ));
    }

    public static function register_settings_page()
    {
        add_submenu_page(
            'jazireh-dashboard',
            'Jazireh Settings',
            'Settings',
            'manage_options',
            'jazireh-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    public static function defaults()
    {
        return array(
            'homepage' => array(
                'hero_kicker' => 'جزیره؛ نجوم و علم به زبان فارسی',
                'hero_title' => 'آسمان را ببینید،',
                'hero_highlight' => 'جهان را بهتر بفهمید.',
                'hero_description' => 'خبرهای علمی، کلاس‌های نجوم، وضعیت رصد، تصویر روز ناسا و تجربه‌های تعاملی فضایی؛ همه در مسیر محتوایی جزیره.',
                'hero_primary_label' => 'آسمان امشب',
                'hero_primary_url' => '/sky',
                'hero_secondary_label' => 'کاوش منظومه شمسی',
                'hero_secondary_url' => '/explore',
                'hero_media_type' => 'video',
                'hero_media_image_id' => 0,
                'hero_media_video_url' => '/media/home-hero-stars.mp4',
                'section_eyebrow' => 'در جزیره چه می‌بینید؟',
                'section_title' => 'از آسمان امشب تا تازه‌ترین روایت‌های علمی',
                'section_description' => 'بخش‌های اصلی جزیره برای یادگیری، رصد و دنبال‌کردن خبرها و ویدیوهای نجومی.',
                'cards' => array(
                    'sky_eyebrow' => 'رصد امشب',
                    'sky_title' => 'آسمان امروز',
                    'sky_cta' => 'جزئیات آسمان',
                    'explore_eyebrow' => 'منظومه شمسی',
                    'explore_title' => 'کاوش سیاره‌ها',
                    'explore_description' => 'انتخاب و مشاهده اطلاعات سیاره‌ها',
                    'explore_cta' => 'شروع کاوش',
                    'news_eyebrow' => 'تازه‌ها',
                    'news_title' => 'آخرین اخبار علمی',
                    'news_cta' => 'همه خبرها',
                    'videos_eyebrow' => 'رسانه تصویری',
                    'videos_title' => 'ویدیوهای جزیره',
                    'videos_cta' => 'مشاهده ویدیوها',
                    'daily_eyebrow' => 'جزیره دیلی',
                    'daily_title' => 'عکس روز ناسا در جزیره',
                    'daily_cta' => 'مشاهده جزیره دیلی',
                    'apod_eyebrow' => 'تصویر نجومی روز',
                    'apod_cta' => 'مشاهده تصویر روز',
                    'radar_eyebrow' => 'آموزشی',
                    'radar_title' => 'شبیه‌ساز رادار آسمان',
                    'radar_cta' => 'باز کردن رادار',
                ),
                'featured_news_ids' => array(),
            ),
            'branding' => array(
                'site_description' => 'رسانه‌ای فارسی برای روایت علمی نجوم، فضا و شگفتی‌های جهان.',
                'youtube_label' => 'کانال یوتیوب جزیره',
            ),
            'social' => array(
                'youtube_url' => 'https://www.youtube.com/@Jazireh',
                'youtube_handle' => '@Jazireh',
                'instagram_url' => '',
                'telegram_url' => 'https://t.me/jaziretv',
                'x_url' => '',
                'linkedin_url' => '',
            ),
            'community' => array(
                'include_phrases' => 'عکس روز ناسا',
                'require_image' => '1',
            ),
            'integrations' => array(
                'apod_auto_localization' => '0',
            ),
            'contact' => array(
                'email' => 'SNazarieh@Hotmail.com',
                'phone' => '',
                'address' => '',
            ),
            'footer' => array(
                'description' => 'رسانه‌ای فارسی برای روایت علمی نجوم، فضا و شگفتی‌های جهان.',
                'microcopy' => 'نجوم، فضا، آموزش و روایت‌های علمی به زبان فارسی.',
                'quicklinks_title' => 'دسترسی سریع',
                'newsletter_title' => 'خبرنامه جزیره',
                'newsletter_description' => 'خلاصه رویدادهای نجومی و تازه‌ترین محتوای جزیره را دریافت کنید.',
                'youtube_title' => 'جزیره در یوتیوب',
                'youtube_description' => 'مستندها، کلاس‌های نجوم، خبرهای علمی و روایت‌های تصویری.',
                'copyright_text' => '',
            ),
        );
    }

    public static function get_settings()
    {
        return wp_parse_args(get_option(self::OPTION_NAME, array()), self::defaults());
    }

    public static function sanitize_settings($input)
    {
        $defaults = self::defaults();
        $input = is_array($input) ? $input : array();
        $output = $defaults;

        $homepage = isset($input['homepage']) && is_array($input['homepage']) ? $input['homepage'] : array();
        $output['homepage']['hero_kicker'] = sanitize_text_field(self::array_get($homepage, 'hero_kicker', $defaults['homepage']['hero_kicker']));
        $output['homepage']['hero_title'] = sanitize_text_field(self::array_get($homepage, 'hero_title', $defaults['homepage']['hero_title']));
        $output['homepage']['hero_highlight'] = sanitize_text_field(self::array_get($homepage, 'hero_highlight', $defaults['homepage']['hero_highlight']));
        $output['homepage']['hero_description'] = sanitize_textarea_field(self::array_get($homepage, 'hero_description', $defaults['homepage']['hero_description']));
        $output['homepage']['hero_primary_label'] = sanitize_text_field(self::array_get($homepage, 'hero_primary_label', $defaults['homepage']['hero_primary_label']));
        $output['homepage']['hero_primary_url'] = self::sanitize_link(self::array_get($homepage, 'hero_primary_url', $defaults['homepage']['hero_primary_url']));
        $output['homepage']['hero_secondary_label'] = sanitize_text_field(self::array_get($homepage, 'hero_secondary_label', $defaults['homepage']['hero_secondary_label']));
        $output['homepage']['hero_secondary_url'] = self::sanitize_link(self::array_get($homepage, 'hero_secondary_url', $defaults['homepage']['hero_secondary_url']));
        $output['homepage']['hero_media_type'] = in_array(self::array_get($homepage, 'hero_media_type', 'video'), array('image', 'video'), true) ? self::array_get($homepage, 'hero_media_type', 'video') : 'video';
        $output['homepage']['hero_media_image_id'] = absint(self::array_get($homepage, 'hero_media_image_id', 0));
        $output['homepage']['hero_media_video_url'] = esc_url_raw(self::array_get($homepage, 'hero_media_video_url', $defaults['homepage']['hero_media_video_url']));
        $output['homepage']['section_eyebrow'] = sanitize_text_field(self::array_get($homepage, 'section_eyebrow', $defaults['homepage']['section_eyebrow']));
        $output['homepage']['section_title'] = sanitize_text_field(self::array_get($homepage, 'section_title', $defaults['homepage']['section_title']));
        $output['homepage']['section_description'] = sanitize_textarea_field(self::array_get($homepage, 'section_description', $defaults['homepage']['section_description']));

        $cards = isset($homepage['cards']) && is_array($homepage['cards']) ? $homepage['cards'] : array();
        foreach ($defaults['homepage']['cards'] as $key => $value) {
            $output['homepage']['cards'][$key] = sanitize_text_field(self::array_get($cards, $key, $value));
        }

        $featured_news_ids = isset($homepage['featured_news_ids']) && is_array($homepage['featured_news_ids']) ? $homepage['featured_news_ids'] : array();
        $output['homepage']['featured_news_ids'] = array_values(array_filter(array_map('absint', $featured_news_ids)));

        $branding = isset($input['branding']) && is_array($input['branding']) ? $input['branding'] : array();
        $output['branding']['site_description'] = sanitize_textarea_field(self::array_get($branding, 'site_description', $defaults['branding']['site_description']));
        $output['branding']['youtube_label'] = sanitize_text_field(self::array_get($branding, 'youtube_label', $defaults['branding']['youtube_label']));

        $social = isset($input['social']) && is_array($input['social']) ? $input['social'] : array();
        $output['social']['youtube_url'] = esc_url_raw(self::array_get($social, 'youtube_url', $defaults['social']['youtube_url']));
        $output['social']['youtube_handle'] = sanitize_text_field(self::array_get($social, 'youtube_handle', $defaults['social']['youtube_handle']));
        $output['social']['instagram_url'] = esc_url_raw(self::array_get($social, 'instagram_url', ''));
        $output['social']['telegram_url'] = esc_url_raw(self::array_get($social, 'telegram_url', ''));
        $output['social']['x_url'] = esc_url_raw(self::array_get($social, 'x_url', ''));
        $output['social']['linkedin_url'] = esc_url_raw(self::array_get($social, 'linkedin_url', ''));

        $community = isset($input['community']) && is_array($input['community']) ? $input['community'] : array();
        $output['community']['include_phrases'] = sanitize_textarea_field(self::array_get($community, 'include_phrases', $defaults['community']['include_phrases']));
        $output['community']['require_image'] = self::array_get($community, 'require_image', '1') === '1' ? '1' : '0';

        $integrations = isset($input['integrations']) && is_array($input['integrations']) ? $input['integrations'] : array();
        $output['integrations']['apod_auto_localization'] = self::array_get($integrations, 'apod_auto_localization', $defaults['integrations']['apod_auto_localization']) === '1' ? '1' : '0';

        $contact = isset($input['contact']) && is_array($input['contact']) ? $input['contact'] : array();
        $output['contact']['email'] = sanitize_email(self::array_get($contact, 'email', ''));
        $output['contact']['phone'] = sanitize_text_field(self::array_get($contact, 'phone', ''));
        $output['contact']['address'] = sanitize_textarea_field(self::array_get($contact, 'address', ''));

        $footer = isset($input['footer']) && is_array($input['footer']) ? $input['footer'] : array();
        foreach ($defaults['footer'] as $key => $value) {
            $output['footer'][$key] = sanitize_textarea_field(self::array_get($footer, $key, $value));
        }

        return $output;
    }

    public static function get_site_payload()
    {
        $settings = self::get_settings();
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_url = $custom_logo_id ? wp_get_attachment_image_url($custom_logo_id, 'full') : '';
        $logo_alt = $custom_logo_id ? get_post_meta($custom_logo_id, '_wp_attachment_image_alt', true) : '';
        $hero_image_id = (int) self::array_get($settings['homepage'], 'hero_media_image_id', 0);
        $hero_image_url = $hero_image_id ? wp_get_attachment_image_url($hero_image_id, 'full') : '';

        return array(
            'identity' => array(
                'name' => get_bloginfo('name'),
                'tagline' => get_bloginfo('description'),
                'description' => self::array_get($settings['branding'], 'site_description', ''),
                'siteUrl' => home_url('/'),
                'logo' => array(
                    'url' => $logo_url ?: '',
                    'alt' => $logo_alt ?: get_bloginfo('name'),
                ),
                'siteIconUrl' => has_site_icon() ? get_site_icon_url(512) : '',
            ),
            'social' => self::public_social_payload($settings),
            'contact' => self::public_contact_payload($settings),
            'menus' => array(
                'primary' => self::menu_items('jazireh_primary_menu', self::default_primary_menu()),
                'mobile' => self::menu_items('jazireh_mobile_menu', self::default_mobile_menu()),
                'footer' => self::menu_items('jazireh_footer_menu', self::default_footer_menu()),
            ),
            'homepage' => array(
                'hero' => array(
                    'kicker' => self::array_get($settings['homepage'], 'hero_kicker', ''),
                    'title' => self::array_get($settings['homepage'], 'hero_title', ''),
                    'highlight' => self::array_get($settings['homepage'], 'hero_highlight', ''),
                    'description' => self::array_get($settings['homepage'], 'hero_description', ''),
                    'primaryAction' => array(
                        'label' => self::array_get($settings['homepage'], 'hero_primary_label', ''),
                        'url' => self::array_get($settings['homepage'], 'hero_primary_url', '/sky'),
                    ),
                    'secondaryAction' => array(
                        'label' => self::array_get($settings['homepage'], 'hero_secondary_label', ''),
                        'url' => self::array_get($settings['homepage'], 'hero_secondary_url', '/explore'),
                    ),
                    'media' => array(
                        'type' => self::array_get($settings['homepage'], 'hero_media_type', 'video'),
                        'imageUrl' => $hero_image_url ?: '',
                        'videoUrl' => self::array_get($settings['homepage'], 'hero_media_video_url', ''),
                    ),
                ),
                'section' => array(
                    'eyebrow' => self::array_get($settings['homepage'], 'section_eyebrow', ''),
                    'title' => self::array_get($settings['homepage'], 'section_title', ''),
                    'description' => self::array_get($settings['homepage'], 'section_description', ''),
                ),
                'cards' => $settings['homepage']['cards'],
                'featuredNewsIds' => $settings['homepage']['featured_news_ids'],
            ),
            'footer' => $settings['footer'],
            'community' => $settings['community'],
        );
    }

    public static function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = self::get_settings();
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'homepage';
        $allowed_tabs = array('homepage', 'branding', 'social', 'contact', 'footer', 'integrations');
        if (!in_array($tab, $allowed_tabs, true)) {
            $tab = 'homepage';
        }
        $refresh_notice = isset($_GET['videos_refresh']) ? sanitize_key(wp_unslash($_GET['videos_refresh'])) : '';
        $refresh_message = isset($_GET['videos_message']) ? sanitize_text_field(wp_unslash($_GET['videos_message'])) : '';
        $widget_notice = isset($_GET['widget_refresh']) ? sanitize_key(wp_unslash($_GET['widget_refresh'])) : '';
        $widget_message = isset($_GET['widget_message']) ? sanitize_text_field(wp_unslash($_GET['widget_message'])) : '';
        $news_items = get_posts(array(
            'post_type' => Jazireh_News::POST_TYPE,
            'post_status' => array('publish', 'draft'),
            'posts_per_page' => 12,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        wp_enqueue_media();
        ?>
        <div class="wrap jazireh-settings-wrap">
            <h1>Jazireh Settings</h1>
            <p>Manage the editable content that powers the React frontend while keeping advanced interactive sections in code.
            </p>
            <nav class="nav-tab-wrapper">
                <?php foreach ($allowed_tabs as $tab_key): ?>
                    <a class="nav-tab <?php echo $tab === $tab_key ? 'nav-tab-active' : ''; ?>"
                        href="<?php echo esc_url(admin_url('admin.php?page=jazireh-settings&tab=' . $tab_key)); ?>"><?php echo esc_html(ucwords(str_replace('-', ' ', $tab_key))); ?></a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="options.php" class="jazireh-settings-form">
                <?php settings_fields('jazireh_settings_group'); ?>
                <?php if ($refresh_notice && $refresh_message): ?>
                    <div
                        class="<?php echo $refresh_notice === 'success' ? 'notice notice-success' : 'notice notice-error'; ?> inline">
                        <p><?php echo esc_html($refresh_message); ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($widget_notice && $widget_message): ?>
                    <div
                        class="<?php echo $widget_notice === 'success' ? 'notice notice-success' : 'notice notice-error'; ?> inline">
                        <p><?php echo esc_html($widget_message); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($tab === 'homepage'): ?>
                    <div class="jazireh-settings-grid">
                        <div class="jazireh-settings-card">
                            <h2>Hero</h2>
                            <?php self::text_input('jazireh_settings[homepage][hero_kicker]', 'Kicker', self::array_get($settings['homepage'], 'hero_kicker', '')); ?>
                            <?php self::text_input('jazireh_settings[homepage][hero_title]', 'Hero title', self::array_get($settings['homepage'], 'hero_title', '')); ?>
                            <?php self::text_input('jazireh_settings[homepage][hero_highlight]', 'Hero highlight', self::array_get($settings['homepage'], 'hero_highlight', '')); ?>
                            <?php self::textarea_input('jazireh_settings[homepage][hero_description]', 'Hero description', self::array_get($settings['homepage'], 'hero_description', '')); ?>
                            <?php self::text_input('jazireh_settings[homepage][hero_primary_label]', 'Primary button label', self::array_get($settings['homepage'], 'hero_primary_label', '')); ?>
                            <?php self::text_input('jazireh_settings[homepage][hero_primary_url]', 'Primary button URL', self::array_get($settings['homepage'], 'hero_primary_url', '')); ?>
                            <?php self::text_input('jazireh_settings[homepage][hero_secondary_label]', 'Secondary button label', self::array_get($settings['homepage'], 'hero_secondary_label', '')); ?>
                            <?php self::text_input('jazireh_settings[homepage][hero_secondary_url]', 'Secondary button URL', self::array_get($settings['homepage'], 'hero_secondary_url', '')); ?>
                            <?php self::select_input('jazireh_settings[homepage][hero_media_type]', 'Hero media type', self::array_get($settings['homepage'], 'hero_media_type', 'video'), array('video' => 'Video', 'image' => 'Image')); ?>
                            <?php self::media_input('jazireh_settings[homepage][hero_media_image_id]', 'Hero image', (int) self::array_get($settings['homepage'], 'hero_media_image_id', 0)); ?>
                            <?php self::text_input('jazireh_settings[homepage][hero_media_video_url]', 'Hero video URL', self::array_get($settings['homepage'], 'hero_media_video_url', '')); ?>
                        </div>

                        <div class="jazireh-settings-card">
                            <h2>Homepage sections</h2>
                            <?php self::text_input('jazireh_settings[homepage][section_eyebrow]', 'Section eyebrow', self::array_get($settings['homepage'], 'section_eyebrow', '')); ?>
                            <?php self::text_input('jazireh_settings[homepage][section_title]', 'Section title', self::array_get($settings['homepage'], 'section_title', '')); ?>
                            <?php self::textarea_input('jazireh_settings[homepage][section_description]', 'Section description', self::array_get($settings['homepage'], 'section_description', '')); ?>
                        </div>
                    </div>

                    <div class="jazireh-settings-card">
                        <h2>Homepage cards</h2>
                        <div class="jazireh-settings-grid two-col">
                            <?php foreach ($settings['homepage']['cards'] as $key => $value): ?>
                                <?php self::text_input('jazireh_settings[homepage][cards][' . $key . ']', ucwords(str_replace('_', ' ', $key)), $value); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="jazireh-settings-card">
                        <h2>Featured content</h2>
                        <p>Select up to three news items to pin on the homepage. If none are selected, the latest published items
                            are used.</p>
                        <div class="jazireh-checkbox-list">
                            <?php foreach ($news_items as $news_item): ?>
                                <label>
                                    <input type="checkbox" name="jazireh_settings[homepage][featured_news_ids][]"
                                        value="<?php echo esc_attr($news_item->ID); ?>" <?php checked(in_array((int) $news_item->ID, self::array_get($settings['homepage'], 'featured_news_ids', array()), true)); ?>>
                                    <span><?php echo esc_html(get_the_title($news_item)); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($tab === 'branding'): ?>
                    <div class="jazireh-settings-card">
                        <h2>Branding and site identity</h2>
                        <p>Use <a href="<?php echo esc_url(admin_url('customize.php')); ?>">Appearance &gt; Customize</a> to change
                            the custom logo and site icon. Site title and tagline come from <a
                                href="<?php echo esc_url(admin_url('options-general.php')); ?>">Settings &gt; General</a>.</p>
                        <?php self::textarea_input('jazireh_settings[branding][site_description]', 'Brand description', self::array_get($settings['branding'], 'site_description', '')); ?>
                        <?php self::text_input('jazireh_settings[branding][youtube_label]', 'YouTube label', self::array_get($settings['branding'], 'youtube_label', '')); ?>
                    </div>
                <?php endif; ?>

                <?php if ($tab === 'social'): ?>
                    <div class="jazireh-settings-card">
                        <h2>Social media</h2>
                        <?php self::text_input('jazireh_settings[social][youtube_url]', 'YouTube URL', self::array_get($settings['social'], 'youtube_url', '')); ?>
                        <?php self::text_input('jazireh_settings[social][youtube_handle]', 'YouTube handle', self::array_get($settings['social'], 'youtube_handle', '')); ?>
                        <?php self::text_input('jazireh_settings[social][instagram_url]', 'Instagram URL', self::array_get($settings['social'], 'instagram_url', '')); ?>
                        <?php self::text_input('jazireh_settings[social][telegram_url]', 'Telegram URL', self::array_get($settings['social'], 'telegram_url', '')); ?>
                        <?php self::text_input('jazireh_settings[social][x_url]', 'X URL', self::array_get($settings['social'], 'x_url', '')); ?>
                        <?php self::text_input('jazireh_settings[social][linkedin_url]', 'LinkedIn URL', self::array_get($settings['social'], 'linkedin_url', '')); ?>
                    </div>
                <?php endif; ?>

                <?php if ($tab === 'contact'): ?>
                    <div class="jazireh-settings-card">
                        <h2>Contact information</h2>
                        <?php self::text_input('jazireh_settings[contact][email]', 'Email', self::array_get($settings['contact'], 'email', '')); ?>
                        <?php self::text_input('jazireh_settings[contact][phone]', 'Phone', self::array_get($settings['contact'], 'phone', '')); ?>
                        <?php self::textarea_input('jazireh_settings[contact][address]', 'Address', self::array_get($settings['contact'], 'address', '')); ?>
                    </div>
                <?php endif; ?>

                <?php if ($tab === 'footer'): ?>
                    <div class="jazireh-settings-card">
                        <h2>Footer content</h2>
                        <?php foreach ($settings['footer'] as $key => $value): ?>
                            <?php self::textarea_input('jazireh_settings[footer][' . $key . ']', ucwords(str_replace('_', ' ', $key)), $value); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($tab === 'integrations'): ?>
                    <div class="jazireh-settings-card">
                        <h2>Integrations</h2>
                        <p>The React frontend reads these WordPress-managed values from <code>/wp-json/jazireh/v1/site</code>.
                            Interactive sections still stay in React, while editable copy, links, branding, and menus come from
                            WordPress.</p>
                        <ul>
                            <li>YouTube API key remains server-side.</li>
                            <li>Logo comes from the WordPress custom logo setting.</li>
                            <li>Site icon comes from the WordPress site icon setting.</li>
                            <li>Header and footer menus come from Appearance &gt; Menus.</li>
                        </ul>
                    </div>
                    <?php self::apod_localization_panel($settings); ?>
                    <div class="jazireh-settings-card">
                        <h2>Community matching</h2>
                        <?php self::textarea_input('jazireh_settings[community][include_phrases]', 'Include phrases (one per line)', self::array_get($settings['community'], 'include_phrases', '')); ?>
                        <div class="jazireh-field">
                            <label>
                                <input type="checkbox" name="jazireh_settings[community][require_image]" value="1" <?php checked(self::array_get($settings['community'], 'require_image', '1'), '1'); ?>>
                                Require at least one image for a Community post match
                            </label>
                        </div>
                    </div>
                    <?php Jazireh_YouTube::integrations_panel(); ?>
                    <?php Jazireh_Daily::integrations_panel(); ?>
                    <?php self::observatory_monitoring_panel(); ?>
                <?php endif; ?>

                <?php submit_button('Save Jazireh Settings'); ?>
            </form>
        </div>
        <style>
            .jazireh-settings-wrap {
                max-width: 1180px
            }

            .jazireh-settings-form {
                margin-top: 20px
            }

            .jazireh-settings-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px
            }

            .jazireh-settings-grid.two-col {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }

            .jazireh-settings-card {
                background: #fff;
                border: 1px solid #dcdcde;
                border-radius: 18px;
                padding: 24px;
                margin-top: 20px
            }

            .jazireh-settings-card h2 {
                margin-top: 0
            }

            .jazireh-field {
                margin-bottom: 16px
            }

            .jazireh-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 6px
            }

            .jazireh-field input[type=text],
            .jazireh-field input[type=url],
            .jazireh-field input[type=email],
            .jazireh-field textarea,
            .jazireh-field select {
                width: 100%;
                max-width: none
            }

            .jazireh-media-preview {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-top: 8px
            }

            .jazireh-media-preview img {
                width: 72px;
                height: 72px;
                object-fit: cover;
                border-radius: 12px;
                border: 1px solid #dcdcde
            }

            .jazireh-checkbox-list {
                display: grid;
                gap: 10px
            }

            .jazireh-checkbox-list label {
                display: flex;
                gap: 10px;
                align-items: flex-start
            }

            .jazireh-widget-table-wrap {
                overflow-x: auto
            }

            .jazireh-widget-table td,
            .jazireh-widget-table th {
                vertical-align: middle
            }

            .jazireh-status-badge {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: 4px 10px;
                font-size: 12px;
                font-weight: 700;
                background: #f1f5f9;
                color: #334155
            }

            .jazireh-status-ready,
            .jazireh-status-warm {
                background: #dcfce7;
                color: #166534
            }

            .jazireh-status-auto_ready,
            .jazireh-status-manual_ready {
                background: #dcfce7;
                color: #166534
            }

            .jazireh-status-stale,
            .jazireh-status-pending {
                background: #fef3c7;
                color: #92400e
            }

            .jazireh-status-error,
            .jazireh-status-failed,
            .jazireh-status-missing_key {
                background: #fee2e2;
                color: #991b1b
            }

            .jazireh-status-miss,
            .jazireh-status-not_checked {
                background: #e2e8f0;
                color: #475569
            }

            @media (max-width: 900px) {

                .jazireh-settings-grid,
                .jazireh-settings-grid.two-col {
                    grid-template-columns: 1fr
                }
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-jazireh-media-field]').forEach(function (wrapper) {
                    var input = wrapper.querySelector('input[type="hidden"]');
                    var button = wrapper.querySelector('[data-jazireh-open-media]');
                    var clear = wrapper.querySelector('[data-jazireh-clear-media]');
                    var preview = wrapper.querySelector('[data-jazireh-media-preview]');
                    if (!button || !input) return;
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        var frame = wp.media({ title: 'Select media', button: { text: 'Use selected media' }, multiple: false });
                        frame.on('select', function () {
                            var attachment = frame.state().get('selection').first().toJSON();
                            input.value = attachment.id || '';
                            if (preview) preview.innerHTML = attachment.url ? '<img src="' + attachment.url + '" alt="">' : '';
                        });
                        frame.open();
                    });
                    if (clear) {
                        clear.addEventListener('click', function (event) {
                            event.preventDefault();
                            input.value = '';
                            if (preview) preview.innerHTML = '';
                        });
                    }
                });
            });
        </script>
        <?php
    }

    public static function handle_widget_refresh()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to refresh Jazireh widgets.', 'jazireh-core'), '', array('response' => 403));
        }

        check_admin_referer('jazireh_refresh_widget');

        $widget = isset($_REQUEST['widget']) ? sanitize_key(wp_unslash($_REQUEST['widget'])) : '';
        $descriptors = self::widget_descriptors();
        $status = 'error';
        $message = 'Unknown widget.';

        if (isset($descriptors[$widget])) {
            $callback = $descriptors[$widget]['refresh_callback'];
            if (is_callable($callback)) {
                $result = call_user_func($callback);
                $is_success = is_array($result) && isset($result['status']) && $result['status'] !== Jazireh_Widgets::STATE_ERROR;
                $status = $is_success ? 'success' : 'error';
                $message = $is_success
                    ? $descriptors[$widget]['name'] . ' refreshed.'
                    : $descriptors[$widget]['name'] . ' refresh failed.';
                if (is_array($result) && !empty($result['message'])) {
                    $message .= ' ' . $result['message'];
                }
            } else {
                $message = 'Refresh callback is unavailable.';
            }
        }

        wp_safe_redirect(add_query_arg(array(
            'page' => 'jazireh-settings',
            'tab' => 'integrations',
            'widget_refresh' => $status,
            'widget_message' => rawurlencode($message),
        ), admin_url('admin.php')));
        exit;
    }

    private static function public_contact_payload($settings)
    {
        $email = sanitize_email(self::array_get($settings['contact'], 'email', ''));

        return array_filter(array(
            'email' => $email,
            'sponsorEmail' => $email,
            'sponsorLabel' => 'همکاری و اسپانسری',
            'sponsorPurpose' => 'صرفاً برای همکاری‌های تجاری و اسپانسری',
            'telegramUrl' => self::array_get($settings['social'], 'telegram_url', ''),
            'instagramUrl' => self::array_get($settings['social'], 'instagram_url', ''),
        ), function ($value) {
            return $value !== '';
        });
    }

    private static function public_social_payload($settings)
    {
        return array_filter(array(
            'youtube' => array_filter(array(
                'url' => self::array_get($settings['social'], 'youtube_url', ''),
                'handle' => self::array_get($settings['social'], 'youtube_handle', ''),
                'label' => self::array_get($settings['branding'], 'youtube_label', ''),
            ), function ($value) {
                return $value !== '';
            }),
            'instagram' => self::array_get($settings['social'], 'instagram_url', ''),
            'telegram' => self::array_get($settings['social'], 'telegram_url', ''),
        ), function ($value) {
            return $value !== '' && $value !== array();
        });
    }

    private static function observatory_monitoring_panel()
    {
        $statuses = Jazireh_Widgets::statuses();
        ?>
        <div class="jazireh-settings-card">
            <h2>Live Observatory monitoring</h2>
            <p>Monitor public observatory widgets and refresh a single widget cache when needed. API keys and secrets are never
                displayed here.</p>
            <div class="jazireh-widget-table-wrap">
                <table class="widefat striped jazireh-widget-table">
                    <thead>
                        <tr>
                            <th>Widget</th>
                            <th>Status</th>
                            <th>Last successful refresh</th>
                            <th>Last error</th>
                            <th>Cache</th>
                            <th>Source / provider</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (self::widget_descriptors() as $key => $descriptor): ?>
                            <?php
                            $status = isset($statuses[$key]) && is_array($statuses[$key]) ? $statuses[$key] : array();
                            $cache_status = self::widget_cache_status($descriptor);
                            $refresh_url = wp_nonce_url(add_query_arg(array(
                                'action' => 'jazireh_refresh_widget',
                                'widget' => $key,
                            ), admin_url('admin-post.php')), 'jazireh_refresh_widget');
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($descriptor['name']); ?></strong><br><code><?php echo esc_html($key); ?></code>
                                </td>
                                <td><?php echo wp_kses_post(self::status_badge(self::array_get($status, 'status', 'not_checked'))); ?>
                                </td>
                                <td><?php echo esc_html(self::admin_datetime(self::array_get($status, 'lastSuccessAt', ''))); ?>
                                </td>
                                <td><?php echo esc_html(self::array_get($status, 'lastError', '') ?: '—'); ?></td>
                                <td><?php echo wp_kses_post(self::status_badge($cache_status)); ?></td>
                                <td><?php echo esc_html(self::array_get($status, 'source', '') ?: $descriptor['source']); ?></td>
                                <td>
                                    <a class="button button-secondary button-small"
                                        href="<?php echo esc_url($refresh_url); ?>">Refresh</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private static function apod_localization_panel($settings)
    {
        $health = class_exists('Jazireh_APOD_Localizer') ? Jazireh_APOD_Localizer::provider_health() : array('status' => 'missing_key', 'label' => 'نیاز به تنظیم کلید سرویس');
        $monitor = class_exists('Jazireh_APOD_Localizer') ? Jazireh_APOD_Localizer::monitor() : array();
        ?>
        <div class="jazireh-settings-card" dir="rtl">
            <h2>ترجمه فارسی عکس روز ناسا</h2>
            <p>حالت پیش‌فرض تولیدی، ترجمه دستی تحریریه است: APOD تازه دریافت می‌شود، متن اصلی NASA و نسخه منبع ذخیره می‌شود و
                ویراستار ترجمه فارسی را در وردپرس ثبت و بازبینی می‌کند. اتصال خودکار فقط یک ابزار کمکی اختیاری آینده است.</p>
            <div class="jazireh-field">
                <label>
                    <input type="checkbox" name="jazireh_settings[integrations][apod_auto_localization]" value="1" <?php checked(self::array_get($settings['integrations'], 'apod_auto_localization', '0'), '1'); ?>>
                    فعال‌سازی کمک‌یار ترجمه خودکار اختیاری
                </label>
            </div>
            <p><strong>وضعیت سرویس:</strong>
                <?php echo wp_kses_post(self::status_badge(self::array_get($health, 'badge', 'not_checked'), self::array_get($health, 'label', 'وضعیت نامشخص'))); ?>
            </p>
            <p><strong>آخرین تاریخ APOD:</strong> <?php echo esc_html(self::array_get($monitor, 'latestDate', '') ?: '—'); ?>
            </p>
            <p><strong>وضعیت ترجمه:</strong>
                <?php echo wp_kses_post(self::status_badge(self::array_get($monitor, 'status', 'not_checked'))); ?></p>
            <p><strong>آخرین تلاش:</strong>
                <?php echo esc_html(self::admin_datetime(self::array_get($monitor, 'lastAttemptAt', ''))); ?></p>
            <p><strong>آخرین موفقیت:</strong>
                <?php echo esc_html(self::admin_datetime(self::array_get($monitor, 'lastSuccessAt', ''))); ?></p>
            <p><strong>خطای اخیر:</strong> <?php echo esc_html(self::array_get($monitor, 'lastError', '') ?: '—'); ?></p>
        </div>
        <?php
    }

    public static function prewarm_widgets()
    {
        $results = array();

        foreach (self::widget_descriptors() as $key => $descriptor) {
            if (empty($descriptor['prewarm_callback']) || !is_callable($descriptor['prewarm_callback'])) {
                $results[$key] = array(
                    'status' => 'skipped',
                    'message' => 'Prewarm callback is unavailable.',
                );
                continue;
            }

            try {
                $started_at = microtime(true);
                $result = call_user_func($descriptor['prewarm_callback']);
                $results[$key] = array(
                    'status' => is_array($result) && isset($result['status']) ? $result['status'] : 'ready',
                    'message' => is_array($result) && !empty($result['message']) ? $result['message'] : '',
                    'durationMs' => (int) round((microtime(true) - $started_at) * 1000),
                );
            } catch (Throwable $exception) {
                $results[$key] = array(
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                    'durationMs' => isset($started_at) ? (int) round((microtime(true) - $started_at) * 1000) : 0,
                );

                error_log(sprintf(
                    'Jazireh widget prewarm failed for %s: %s',
                    sanitize_key($key),
                    $exception->getMessage()
                ));
            }
        }

        return $results;
    }

    public static function widget_descriptors()
    {
        return array(
            'apod' => array(
                'name' => 'NASA APOD',
                'source' => 'NASA APOD',
                'cache_key' => 'jazireh_apod_latest_range_1',
                'prewarm_callback' => array('Jazireh_APOD_Service', 'refresh'),
                'refresh_callback' => array('Jazireh_APOD_Service', 'refresh'),
            ),
            'sun' => array(
                'name' => 'Sun Now',
                'source' => 'Helioviewer',
                'cache_key' => Jazireh_Widgets::cache_key('sun'),
                'prewarm_callback' => array('Jazireh_Sun_Service', 'refresh'),
                'refresh_callback' => array('Jazireh_Sun_Service', 'refresh'),
            ),
            'moon' => array(
                'name' => 'Moon Now',
                'source' => 'Astronomical calculation',
                'cache_key' => Jazireh_Widgets::cache_key('moon'),
                'prewarm_callback' => array('Jazireh_Moon_Service', 'widget'),
                'refresh_callback' => array('Jazireh_Moon_Service', 'refresh'),
            ),
            'sky' => array(
                'name' => 'Sky Tonight',
                'source' => 'Astronomical calculation',
                'cache_key' => Jazireh_Widgets::cache_key('sky'),
                'prewarm_callback' => array('Jazireh_Sky_Service', 'widget'),
                'refresh_callback' => array('Jazireh_Sky_Service', 'refresh'),
            ),
            'earth' => array(
                'name' => 'Earth From Space',
                'source' => 'NASA EPIC',
                'cache_key' => Jazireh_Widgets::cache_key('earth'),
                'prewarm_callback' => array('Jazireh_Earth_Service', 'refresh'),
                'refresh_callback' => array('Jazireh_Earth_Service', 'refresh'),
            ),
            'earthquakes' => array(
                'name' => 'Earthquakes',
                'source' => 'USGS Earthquake Hazards Program',
                'cache_key' => Jazireh_Widgets::cache_key('earthquakes'),
                'prewarm_callback' => array('Jazireh_Earthquake_Service', 'refresh'),
                'refresh_callback' => array('Jazireh_Earthquake_Service', 'refresh'),
            ),
            'planets' => array(
                'name' => 'Planet Visibility',
                'source' => 'JPL Horizons',
                'cache_key' => 'jazireh_planets_payload_last_good',
                'prewarm_callback' => array('Jazireh_Planets', 'prewarm'),
                'refresh_callback' => array('Jazireh_Planets', 'prewarm'),
            ),
            'youtube' => array(
                'name' => 'YouTube Videos',
                'source' => 'YouTube Data API / official feed',
                'cache_key' => Jazireh_YouTube::CACHE_PREFIX . '3',
                'prewarm_callback' => array('Jazireh_YouTube', 'prewarm'),
                'refresh_callback' => array('Jazireh_YouTube', 'prewarm'),
            ),
        );
    }

    private static function widget_cache_status($descriptor)
    {
        $cached = get_transient($descriptor['cache_key']);
        if (!is_array($cached)) {
            return 'miss';
        }
        return Jazireh_Widgets::is_stale($cached) ? 'stale' : 'warm';
    }

    private static function status_badge($status, $message = '')
    {
        $status = sanitize_key($status);
        $labels = array(
            'ready' => 'Ready',
            'stale' => 'Stale',
            'error' => 'Error',
            'warm' => 'Warm',
            'miss' => 'Miss',
            'not_checked' => 'Not checked',
            'auto_ready' => 'ترجمه خودکار آماده است',
            'manual_ready' => 'ویرایش دستی شده',
            'pending' => 'در انتظار ترجمه تحریریه',
            'failed' => 'ترجمه خودکار انجام نشد',
            'missing_key' => 'نیاز به تنظیم کلید سرویس',
        );
        $label = $message ?: (isset($labels[$status]) ? $labels[$status] : ucfirst($status));
        return '<span class="jazireh-status-badge jazireh-status-' . esc_attr($status) . '">' . esc_html($label) . '</span>';
    }

    private static function admin_datetime($value)
    {
        if (!$value) {
            return '—';
        }
        $timestamp = strtotime((string) $value);
        if (!$timestamp) {
            return '—';
        }
        return wp_date('Y-m-d H:i', $timestamp);
    }

    public static function featured_news_query_args($fallback_count)
    {
        $settings = self::get_settings();
        $featured_ids = array_slice(self::array_get($settings['homepage'], 'featured_news_ids', array()), 0, 3);
        if (empty($featured_ids)) {
            return array(
                'post_type' => Jazireh_News::POST_TYPE,
                'post_status' => 'publish',
                'posts_per_page' => $fallback_count,
                'meta_query' => array(
                    'relation' => 'OR',
                    array('key' => Jazireh_News::META_FEATURED, 'compare' => 'EXISTS'),
                    array('key' => Jazireh_News::META_FEATURED, 'compare' => 'NOT EXISTS'),
                ),
                'orderby' => array('meta_value_num' => 'DESC', 'date' => 'DESC'),
                'no_found_rows' => true,
            );
        }
        return array(
            'post_type' => Jazireh_News::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => count($featured_ids),
            'post__in' => $featured_ids,
            'orderby' => 'post__in',
            'no_found_rows' => true,
        );
    }

    private static function default_primary_menu()
    {
        return array(
            array('label' => 'خانه', 'path' => '/'),
            array('label' => 'آسمان', 'path' => '/sky'),
            array('label' => 'کاوش', 'path' => '/explore'),
            array('label' => 'اخبار', 'path' => '/news'),
            array('label' => 'تصویر روز', 'path' => '/apod'),
            array('label' => 'جزیره دیلی', 'path' => '/jazireh-daily'),
            array('label' => 'ویدیوها', 'path' => '/videos'),
            array('label' => 'رادار', 'path' => '/radar'),
        );
    }

    private static function default_mobile_menu()
    {
        return array(
            array('label' => 'خانه', 'path' => '/', 'icon' => 'home'),
            array('label' => 'آسمان', 'path' => '/sky', 'icon' => 'sparkles'),
            array('label' => 'کاوش', 'path' => '/explore', 'icon' => 'telescope'),
            array('label' => 'اخبار', 'path' => '/news', 'icon' => 'newspaper'),
            array('label' => 'ویدیو', 'path' => '/videos', 'icon' => 'clapperboard'),
        );
    }

    private static function default_footer_menu()
    {
        return array(
            array('label' => 'آسمان امروز', 'path' => '/sky'),
            array('label' => 'کاوش منظومه شمسی', 'path' => '/explore'),
            array('label' => 'تصویر روز ناسا', 'path' => '/apod'),
            array('label' => 'جزیره دیلی', 'path' => '/jazireh-daily'),
            array('label' => 'آخرین اخبار علمی', 'path' => '/news'),
        );
    }

    private static function menu_items($location, $fallback)
    {
        $locations = get_nav_menu_locations();
        if (empty($locations[$location])) {
            return $fallback;
        }
        $menu_items = wp_get_nav_menu_items($locations[$location]);
        if (empty($menu_items) || is_wp_error($menu_items)) {
            return $fallback;
        }

        $items = array();
        foreach ($menu_items as $menu_item) {
            $parsed = self::menu_link_details($menu_item->url);
            $items[] = array(
                'label' => html_entity_decode($menu_item->title, ENT_QUOTES, 'UTF-8'),
                'url' => $menu_item->url,
                'path' => $parsed['path'],
                'external' => $parsed['external'],
                'target' => $menu_item->target ?: '',
                'icon' => '',
            );
        }
        return $items;
    }

    private static function menu_link_details($url)
    {
        $site_host = wp_parse_url(home_url('/'), PHP_URL_HOST);
        $url_host = wp_parse_url($url, PHP_URL_HOST);
        $url_path = wp_parse_url($url, PHP_URL_PATH);
        $site_path = wp_parse_url(home_url('/'), PHP_URL_PATH);

        if (!$url_host || $url_host === $site_host) {
            if ($site_path && strpos((string) $url_path, $site_path) === 0) {
                $url_path = substr((string) $url_path, strlen($site_path));
            }
            $url_path = '/' . ltrim((string) $url_path, '/');
            if ($url_path === '//') {
                $url_path = '/';
            }
            return array('external' => false, 'path' => $url_path);
        }

        return array('external' => true, 'path' => '');
    }

    private static function array_get($array, $key, $default = '')
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    private static function sanitize_link($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (strpos($value, '/') === 0) {
            return '/' . ltrim($value, '/');
        }
        return esc_url_raw($value);
    }

    private static function text_input($name, $label, $value)
    {
        ?>
        <div class="jazireh-field">
            <label><?php echo esc_html($label); ?></label>
            <input type="text" class="regular-text" name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>">
        </div>
        <?php
    }

    private static function textarea_input($name, $label, $value)
    {
        ?>
        <div class="jazireh-field">
            <label><?php echo esc_html($label); ?></label>
            <textarea rows="4" name="<?php echo esc_attr($name); ?>"><?php echo esc_textarea($value); ?></textarea>
        </div>
        <?php
    }

    private static function select_input($name, $label, $value, $options)
    {
        ?>
        <div class="jazireh-field">
            <label><?php echo esc_html($label); ?></label>
            <select name="<?php echo esc_attr($name); ?>">
                <?php foreach ($options as $option_value => $option_label): ?>
                    <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                        <?php echo esc_html($option_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    private static function media_input($name, $label, $attachment_id)
    {
        $preview = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'medium') : '';
        ?>
        <div class="jazireh-field" data-jazireh-media-field>
            <label><?php echo esc_html($label); ?></label>
            <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($attachment_id); ?>">
            <div class="jazireh-media-preview" data-jazireh-media-preview><?php if ($preview): ?><img
                        src="<?php echo esc_url($preview); ?>" alt=""><?php endif; ?></div>
            <p>
                <button class="button" data-jazireh-open-media>Select image</button>
                <button class="button-link-delete" data-jazireh-clear-media>Clear</button>
            </p>
        </div>
        <?php
    }
}
