<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_APOD_Editorial
{
    const POST_TYPE = 'jazireh_apod_fa';
    const META_DATE = '_jazireh_apod_date';
    const META_TITLE_FA = '_jazireh_apod_title_fa';
    const META_SUMMARY_FA = '_jazireh_apod_summary_fa';
    const META_CONTENT_FA = '_jazireh_apod_content_fa';
    const META_STATUS = '_jazireh_apod_translation_status';
    const META_SOURCE_ITEM = '_jazireh_apod_source_item';
    const META_SOURCE_HASH = '_jazireh_apod_source_hash';
    const META_TRANSLATION_SOURCE_HASH = '_jazireh_apod_translation_source_hash';
    const META_TRANSLATED_AT = '_jazireh_apod_translated_at';
    const META_REVIEWED_AT = '_jazireh_apod_reviewed_at';
    const META_ATTEMPTS = '_jazireh_apod_localizer_attempts';
    const META_LAST_ATTEMPT_AT = '_jazireh_apod_localizer_last_attempt_at';
    const META_LAST_SUCCESS_AT = '_jazireh_apod_localizer_last_success_at';
    const META_LAST_ERROR = '_jazireh_apod_localizer_last_error';
    const NONCE_ACTION = 'jazireh_apod_editorial_save';
    const NONCE_NAME = 'jazireh_apod_editorial_nonce';
    const REGENERATE_ACTION = 'jazireh_apod_regenerate';
    const STATUS_PENDING = 'pending';
    const STATUS_AUTO_READY = 'auto_ready';
    const STATUS_MANUAL_READY = 'manual_ready';
    const STATUS_FAILED = 'failed';
    const STATUS_DRAFT = 'draft';
    const STATUS_READY = 'ready';
    const STATUS_STALE = 'stale';

    public static function boot()
    {
        add_action('init', array(__CLASS__, 'register_content_type'));
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'save'), 10, 2);
        add_action('admin_post_jazireh_regenerate_apod_translation', array(__CLASS__, 'handle_regenerate'));
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array(__CLASS__, 'columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array(__CLASS__, 'column_content'), 10, 2);
        add_action('admin_notices', array(__CLASS__, 'admin_notices'));
    }

    public static function register_content_type()
    {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => 'ترجمه‌های APOD',
                'singular_name' => 'ترجمه APOD',
                'add_new_item' => 'افزودن ترجمه APOD',
                'edit_item' => 'ویرایش ترجمه APOD',
                'new_item' => 'ترجمه APOD جدید',
                'view_item' => 'مشاهده ترجمه APOD',
                'search_items' => 'جستجو در ترجمه‌های APOD',
                'not_found' => 'ترجمه‌ای پیدا نشد',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_rest' => false,
            'supports' => array('title'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'menu_icon' => 'dashicons-translation',
        ));
    }

    public static function add_meta_boxes()
    {
        add_meta_box(
            'jazireh-apod-editorial-fields',
            'محتوای فارسی تصویر روز ناسا',
            array(__CLASS__, 'render_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function render_meta_box(WP_Post $post)
    {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
        $date = get_post_meta($post->ID, self::META_DATE, true);
        $title = get_post_meta($post->ID, self::META_TITLE_FA, true);
        $summary = get_post_meta($post->ID, self::META_SUMMARY_FA, true);
        $content = get_post_meta($post->ID, self::META_CONTENT_FA, true);
        $status = get_post_meta($post->ID, self::META_STATUS, true) ?: self::STATUS_DRAFT;
        $source_item = get_post_meta($post->ID, self::META_SOURCE_ITEM, true);
        $source_hash = get_post_meta($post->ID, self::META_SOURCE_HASH, true);
        $translation_source_hash = get_post_meta($post->ID, self::META_TRANSLATION_SOURCE_HASH, true);
        $translated_at = get_post_meta($post->ID, self::META_TRANSLATED_AT, true);
        $reviewed_at = get_post_meta($post->ID, self::META_REVIEWED_AT, true);
        ?>
        <div class="jazireh-apod-editorial-fields" dir="rtl">
            <p>
                <label for="jazireh_apod_date"><strong>تاریخ APOD</strong></label>
                <input type="date" id="jazireh_apod_date" name="jazireh_apod_date" value="<?php echo esc_attr($date); ?>" class="widefat" required>
                <span class="description">این تاریخ باید با تاریخ NASA APOD برابر باشد؛ مثل 2026-08-25.</span>
            </p>
            <p>
                <label for="jazireh_apod_title_fa"><strong>عنوان فارسی</strong></label>
                <input type="text" id="jazireh_apod_title_fa" name="jazireh_apod_title_fa" value="<?php echo esc_attr($title); ?>" class="widefat" dir="rtl">
            </p>
            <p>
                <label for="jazireh_apod_summary_fa"><strong>خلاصه فارسی</strong></label>
                <textarea id="jazireh_apod_summary_fa" name="jazireh_apod_summary_fa" rows="4" class="widefat" dir="rtl"><?php echo esc_textarea($summary); ?></textarea>
            </p>
            <p>
                <label for="jazireh_apod_content_fa"><strong>توضیح کامل فارسی، اختیاری</strong></label>
                <textarea id="jazireh_apod_content_fa" name="jazireh_apod_content_fa" rows="8" class="widefat" dir="rtl"><?php echo esc_textarea($content); ?></textarea>
            </p>
            <p>
                <label for="jazireh_apod_translation_status"><strong>وضعیت محتوای فارسی</strong></label>
                <select id="jazireh_apod_translation_status" name="jazireh_apod_translation_status">
                    <option value="draft" <?php selected($status, self::STATUS_DRAFT); ?>>پیش‌نویس</option>
                    <option value="manual_ready" <?php selected($status, self::STATUS_MANUAL_READY); ?>>ویرایش دستی شده</option>
                    <option value="auto_ready" <?php selected($status, self::STATUS_AUTO_READY); ?>>ترجمه خودکار آماده است</option>
                    <option value="pending" <?php selected($status, self::STATUS_PENDING); ?>>در انتظار ترجمه خودکار</option>
                    <option value="failed" <?php selected($status, self::STATUS_FAILED); ?>>ترجمه خودکار انجام نشد</option>
                    <option value="stale" <?php selected($status, self::STATUS_STALE); ?>>قدیمی؛ منبع ناسا تغییر کرده است</option>
                </select>
            </p>
            <div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#fff;margin:16px 0" dir="ltr">
                <p><strong>NASA original title:</strong><br><?php echo esc_html(is_array($source_item) ? (string) ($source_item['titleOriginal'] ?? '') : ''); ?></p>
                <p><strong>NASA original explanation:</strong><br><?php echo esc_html(is_array($source_item) ? (string) ($source_item['contentOriginal'] ?? '') : ''); ?></p>
                <p><strong>Current source hash:</strong> <code><?php echo esc_html((string) $source_hash); ?></code></p>
                <p><strong>Translation source hash:</strong> <code><?php echo esc_html((string) $translation_source_hash); ?></code></p>
                <p><strong>Translated at:</strong> <?php echo esc_html((string) $translated_at ?: '-'); ?> | <strong>Reviewed at:</strong> <?php echo esc_html((string) $reviewed_at ?: '-'); ?></p>
            </div>
            <?php if ($post->ID && class_exists('Jazireh_APOD_Localizer')) : ?>
                <p>
                    <?php $url = wp_nonce_url(add_query_arg(array('action' => 'jazireh_regenerate_apod_translation', 'post_id' => $post->ID), admin_url('admin-post.php')), self::REGENERATE_ACTION); ?>
                    <a class="button button-secondary" href="<?php echo esc_url($url); ?>">تولید دوباره ترجمه خودکار</a>
                    <span class="description">اگر این نوشته ویرایش دستی شده باشد، تولید دوباره فقط با اقدام آگاهانه مدیر انجام می‌شود.</span>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function save($post_id, WP_Post $post)
    {
        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $date = self::sanitize_date(isset($_POST['jazireh_apod_date']) ? wp_unslash($_POST['jazireh_apod_date']) : '');
        $previous_status = get_post_meta($post_id, self::META_STATUS, true);
        $status = sanitize_key(isset($_POST['jazireh_apod_translation_status']) ? wp_unslash($_POST['jazireh_apod_translation_status']) : self::STATUS_DRAFT);
        if ($status === self::STATUS_READY) {
            $status = self::STATUS_MANUAL_READY;
        }
        if (!in_array($status, self::statuses(), true)) {
            $status = self::STATUS_DRAFT;
        }
        if ($status === self::STATUS_AUTO_READY && $previous_status === self::STATUS_AUTO_READY) {
            $status = self::STATUS_MANUAL_READY;
        } elseif ($status === self::STATUS_AUTO_READY && $previous_status !== self::STATUS_PENDING) {
            $status = self::STATUS_MANUAL_READY;
        }

        update_post_meta($post_id, self::META_DATE, $date);
        update_post_meta($post_id, self::META_TITLE_FA, sanitize_text_field(isset($_POST['jazireh_apod_title_fa']) ? wp_unslash($_POST['jazireh_apod_title_fa']) : ''));
        update_post_meta($post_id, self::META_SUMMARY_FA, sanitize_textarea_field(isset($_POST['jazireh_apod_summary_fa']) ? wp_unslash($_POST['jazireh_apod_summary_fa']) : ''));
        update_post_meta($post_id, self::META_CONTENT_FA, wp_kses_post(isset($_POST['jazireh_apod_content_fa']) ? wp_unslash($_POST['jazireh_apod_content_fa']) : ''));
        update_post_meta($post_id, self::META_STATUS, $status);
        if ($date && in_array($status, array(self::STATUS_MANUAL_READY, self::STATUS_READY), true)) {
            $source_hash = get_post_meta($post_id, self::META_SOURCE_HASH, true);
            if ($source_hash) {
                update_post_meta($post_id, self::META_TRANSLATION_SOURCE_HASH, sanitize_text_field((string) $source_hash));
                update_post_meta($post_id, self::META_REVIEWED_AT, current_time(DATE_ATOM));
            }
        }

        if ($date && self::find_duplicate($date, $post_id)) {
            update_post_meta($post_id, self::META_STATUS, self::STATUS_DRAFT);
            set_transient('jazireh_apod_editorial_duplicate_' . get_current_user_id(), $date, 30);
        }
    }

    public static function get_ready_for_date($date, $source_hash = '')
    {
        $date = self::sanitize_date($date);
        if (!$date) {
            return null;
        }
        $source_hash = self::sanitize_hash($source_hash);

        $posts = get_posts(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => self::META_DATE, 'value' => $date),
                array('key' => self::META_STATUS, 'value' => array(self::STATUS_READY, self::STATUS_AUTO_READY, self::STATUS_MANUAL_READY), 'compare' => 'IN'),
            ),
            'no_found_rows' => true,
        ));

        if (!$posts) {
            return null;
        }

        $post_id = (int) $posts[0]->ID;
        $translation_hash = self::sanitize_hash(get_post_meta($post_id, self::META_TRANSLATION_SOURCE_HASH, true));
        $current_source_hash = self::sanitize_hash(get_post_meta($post_id, self::META_SOURCE_HASH, true));
        if ($source_hash && $translation_hash !== $source_hash) {
            self::mark_stale($post_id, $source_hash);
            return null;
        }
        return array(
            'titleFa' => self::clean_text(get_post_meta($post_id, self::META_TITLE_FA, true)),
            'summaryFa' => self::clean_text(get_post_meta($post_id, self::META_SUMMARY_FA, true)),
            'contentFa' => wp_kses_post(get_post_meta($post_id, self::META_CONTENT_FA, true)),
            'translationStatus' => get_post_meta($post_id, self::META_STATUS, true) ?: self::STATUS_READY,
            'translationSourceHash' => $translation_hash,
            'sourceHash' => $source_hash ?: $current_source_hash,
            'translatedAt' => get_post_meta($post_id, self::META_TRANSLATED_AT, true) ?: get_post_meta($post_id, self::META_LAST_SUCCESS_AT, true),
            'reviewedAt' => get_post_meta($post_id, self::META_REVIEWED_AT, true) ?: '',
            'editorialId' => $post_id,
        );
    }

    public static function has_usable_for_date($date, $source_hash = '')
    {
        return (bool) self::get_ready_for_date($date, $source_hash);
    }

    public static function find_id_by_date($date)
    {
        $date = self::sanitize_date($date);
        if (!$date) {
            return 0;
        }
        $posts = get_posts(array(
            'post_type' => self::POST_TYPE,
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(array('key' => self::META_DATE, 'value' => $date)),
            'no_found_rows' => true,
        ));
        return $posts ? (int) $posts[0]->ID : 0;
    }

    public static function ensure_pending(array $item)
    {
        $date = self::sanitize_date($item['date'] ?? '');
        if (!$date) {
            return 0;
        }
        self::remember_source_item($item);
        $source_hash = self::source_hash($item);
        $post_id = self::find_id_by_date($date);
        if ($post_id) {
            $status = get_post_meta($post_id, self::META_STATUS, true);
            $translation_hash = self::sanitize_hash(get_post_meta($post_id, self::META_TRANSLATION_SOURCE_HASH, true));
            update_post_meta($post_id, self::META_SOURCE_HASH, $source_hash);
            if (in_array($status, array(self::STATUS_READY, self::STATUS_AUTO_READY, self::STATUS_MANUAL_READY), true) && $translation_hash === $source_hash) {
                return $post_id;
            }
            if (in_array($status, array(self::STATUS_READY, self::STATUS_AUTO_READY, self::STATUS_MANUAL_READY), true) && $translation_hash !== $source_hash) {
                self::mark_stale($post_id, $source_hash);
            }
            if (!$status) {
                update_post_meta($post_id, self::META_STATUS, self::STATUS_PENDING);
            } elseif ($status === self::STATUS_STALE) {
                update_post_meta($post_id, self::META_STATUS, self::STATUS_PENDING);
            }
            return $post_id;
        }

        $post_id = wp_insert_post(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => 'APOD فارسی ' . $date,
        ), true);
        if (is_wp_error($post_id) || !$post_id) {
            return 0;
        }

        update_post_meta($post_id, self::META_DATE, $date);
        update_post_meta($post_id, self::META_STATUS, self::STATUS_PENDING);
        update_post_meta($post_id, self::META_SOURCE_ITEM, self::sanitize_source_item($item));
        update_post_meta($post_id, self::META_SOURCE_HASH, $source_hash);
        return (int) $post_id;
    }

    public static function remember_source_item(array $item)
    {
        $date = self::sanitize_date($item['date'] ?? '');
        if (!$date) {
            return;
        }
        set_transient('jazireh_apod_source_' . $date, self::sanitize_source_item($item), DAY_IN_SECONDS);
        $post_id = self::find_id_by_date($date);
        if ($post_id) {
            update_post_meta($post_id, self::META_SOURCE_ITEM, self::sanitize_source_item($item));
            $new_hash = self::source_hash($item);
            $old_hash = self::sanitize_hash(get_post_meta($post_id, self::META_SOURCE_HASH, true));
            update_post_meta($post_id, self::META_SOURCE_HASH, $new_hash);
            if ($old_hash && $old_hash !== $new_hash) {
                self::mark_stale($post_id, $new_hash);
            }
        }
    }

    public static function source_item_for_date($date)
    {
        $date = self::sanitize_date($date);
        if (!$date) {
            return null;
        }
        $post_id = self::find_id_by_date($date);
        if ($post_id) {
            $stored = get_post_meta($post_id, self::META_SOURCE_ITEM, true);
            if (is_array($stored) && !empty($stored['date'])) {
                return $stored;
            }
        }
        $transient = get_transient('jazireh_apod_source_' . $date);
        return is_array($transient) && !empty($transient['date']) ? $transient : null;
    }

    public static function record_attempt($post_id)
    {
        $post_id = (int) $post_id;
        $attempts = (int) get_post_meta($post_id, self::META_ATTEMPTS, true);
        update_post_meta($post_id, self::META_ATTEMPTS, $attempts + 1);
        update_post_meta($post_id, self::META_LAST_ATTEMPT_AT, current_time(DATE_ATOM));
    }

    public static function mark_auto_ready($post_id, array $fields)
    {
        $post_id = (int) $post_id;
        $current = get_post_meta($post_id, self::META_STATUS, true);
        $source_hash = self::sanitize_hash((string) ($fields['sourceHash'] ?? get_post_meta($post_id, self::META_SOURCE_HASH, true)));
        if ($current === self::STATUS_MANUAL_READY && self::sanitize_hash(get_post_meta($post_id, self::META_TRANSLATION_SOURCE_HASH, true)) === $source_hash) {
            return false;
        }
        update_post_meta($post_id, self::META_TITLE_FA, sanitize_text_field($fields['titleFa'] ?? ''));
        update_post_meta($post_id, self::META_SUMMARY_FA, sanitize_textarea_field($fields['summaryFa'] ?? ''));
        update_post_meta($post_id, self::META_CONTENT_FA, sanitize_textarea_field($fields['contentFa'] ?? ''));
        update_post_meta($post_id, self::META_STATUS, self::STATUS_AUTO_READY);
        update_post_meta($post_id, self::META_TRANSLATION_SOURCE_HASH, $source_hash);
        update_post_meta($post_id, self::META_TRANSLATED_AT, current_time(DATE_ATOM));
        update_post_meta($post_id, self::META_LAST_SUCCESS_AT, current_time(DATE_ATOM));
        update_post_meta($post_id, self::META_LAST_ERROR, '');
        return true;
    }

    public static function mark_failed($post_id, $error)
    {
        $post_id = (int) $post_id;
        $current = get_post_meta($post_id, self::META_STATUS, true);
        if ($current === self::STATUS_MANUAL_READY || $current === self::STATUS_AUTO_READY || $current === self::STATUS_READY) {
            return false;
        }
        update_post_meta($post_id, self::META_STATUS, self::STATUS_FAILED);
        update_post_meta($post_id, self::META_LAST_ERROR, sanitize_text_field((string) $error));
        return true;
    }

    public static function is_retry_allowed($date, $retry_after)
    {
        $post_id = self::find_id_by_date($date);
        if (!$post_id) {
            return true;
        }
        $status = get_post_meta($post_id, self::META_STATUS, true);
        if (in_array($status, array(self::STATUS_READY, self::STATUS_AUTO_READY, self::STATUS_MANUAL_READY), true)) {
            return false;
        }
        $last_attempt = get_post_meta($post_id, self::META_LAST_ATTEMPT_AT, true);
        if (!$last_attempt) {
            return true;
        }
        $timestamp = strtotime($last_attempt);
        return !$timestamp || $timestamp + (int) $retry_after <= current_time('timestamp', true);
    }

    public static function columns($columns)
    {
        $next = array();
        foreach ($columns as $key => $label) {
            $next[$key] = $label;
            if ($key === 'title') {
                $next['apod_date'] = 'تاریخ APOD';
                $next['translation_status'] = 'وضعیت';
            }
        }
        return $next;
    }

    public static function column_content($column, $post_id)
    {
        if ($column === 'apod_date') {
            echo esc_html(get_post_meta($post_id, self::META_DATE, true) ?: '-');
        }
        if ($column === 'translation_status') {
            $status = get_post_meta($post_id, self::META_STATUS, true) ?: self::STATUS_DRAFT;
            echo wp_kses_post(self::status_badge($status));
        }
    }

    public static function handle_regenerate()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to regenerate APOD localization.', 'jazireh-core'), '', array('response' => 403));
        }
        check_admin_referer(self::REGENERATE_ACTION);

        $post_id = isset($_GET['post_id']) ? absint(wp_unslash($_GET['post_id'])) : 0;
        $force = get_post_meta($post_id, self::META_STATUS, true) === self::STATUS_MANUAL_READY;
        $result = class_exists('Jazireh_APOD_Localizer') ? Jazireh_APOD_Localizer::regenerate($post_id, $force) : new WP_Error('jazireh_apod_localizer_missing', 'Localizer unavailable.');
        $notice = is_wp_error($result) ? 'error' : 'success';
        $message = is_wp_error($result)
            ? (class_exists('Jazireh_APOD_Localizer') ? Jazireh_APOD_Localizer::human_error_label($result->get_error_code()) : 'ترجمه خودکار انجام نشد.')
            : 'ترجمه خودکار تولید شد.';

        wp_safe_redirect(add_query_arg(array(
            'post' => $post_id,
            'action' => 'edit',
            'jazireh_apod_regenerate' => $notice,
            'jazireh_apod_message' => rawurlencode($message),
        ), admin_url('post.php')));
        exit;
    }

    public static function admin_notices()
    {
        $key = 'jazireh_apod_editorial_duplicate_' . get_current_user_id();
        $date = get_transient($key);
        if (!$date) {
            $regenerate_notice = isset($_GET['jazireh_apod_regenerate']) ? sanitize_key(wp_unslash($_GET['jazireh_apod_regenerate'])) : '';
            $regenerate_message = isset($_GET['jazireh_apod_message']) ? sanitize_text_field(wp_unslash($_GET['jazireh_apod_message'])) : '';
            if ($regenerate_notice && $regenerate_message) {
                echo '<div class="notice notice-' . esc_attr($regenerate_notice === 'success' ? 'success' : 'error') . ' is-dismissible"><p>' . esc_html($regenerate_message) . '</p></div>';
            }
            return;
        }
        delete_transient($key);
        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html('برای تاریخ ' . $date . ' قبلاً یک ترجمه APOD ثبت شده است. ورودی جدید به حالت پیش‌نویس برگشت تا محتوای تکراری نمایش داده نشود.') . '</p></div>';
    }

    private static function find_duplicate($date, $exclude_id)
    {
        $posts = get_posts(array(
            'post_type' => self::POST_TYPE,
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'posts_per_page' => 1,
            'post__not_in' => array((int) $exclude_id),
            'meta_query' => array(array('key' => self::META_DATE, 'value' => $date)),
            'no_found_rows' => true,
        ));
        return !empty($posts);
    }

    private static function statuses()
    {
        return array(self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_AUTO_READY, self::STATUS_MANUAL_READY, self::STATUS_FAILED, self::STATUS_READY, self::STATUS_STALE);
    }

    private static function status_badge($status)
    {
        $status = sanitize_key($status);
        $labels = array(
            self::STATUS_DRAFT => 'پیش‌نویس',
            self::STATUS_PENDING => 'در انتظار ترجمه خودکار',
            self::STATUS_AUTO_READY => 'ترجمه خودکار آماده است',
            self::STATUS_MANUAL_READY => 'ویرایش دستی شده',
            self::STATUS_FAILED => 'ترجمه خودکار انجام نشد',
            self::STATUS_STALE => 'قدیمی؛ منبع ناسا تغییر کرده است',
            self::STATUS_READY => 'آماده انتشار',
        );
        $classes = array(
            self::STATUS_AUTO_READY => 'background:#dcfce7;color:#166534',
            self::STATUS_MANUAL_READY => 'background:#dcfce7;color:#166534',
            self::STATUS_PENDING => 'background:#fef3c7;color:#92400e',
            self::STATUS_FAILED => 'background:#fee2e2;color:#991b1b',
            self::STATUS_STALE => 'background:#ffedd5;color:#9a3412',
            self::STATUS_DRAFT => 'background:#e2e8f0;color:#475569',
            self::STATUS_READY => 'background:#dcfce7;color:#166534',
        );
        return '<span style="display:inline-flex;border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700;' . esc_attr($classes[$status] ?? $classes[self::STATUS_DRAFT]) . '">' . esc_html($labels[$status] ?? $status) . '</span>';
    }

    private static function sanitize_source_item(array $item)
    {
        return array(
            'id' => isset($item['id']) ? absint($item['id']) : 0,
            'date' => self::sanitize_date($item['date'] ?? ''),
            'title' => self::clean_text(($item['titleOriginal'] ?? '') ?: ($item['title'] ?? 'NASA APOD')),
            'titleOriginal' => self::clean_text(($item['titleOriginal'] ?? '') ?: ($item['title'] ?? 'NASA APOD')),
            'content' => wp_strip_all_tags((string) (($item['contentOriginal'] ?? '') ?: ($item['content'] ?? ''))),
            'contentOriginal' => wp_strip_all_tags((string) (($item['contentOriginal'] ?? '') ?: ($item['content'] ?? ''))),
            'excerpt' => self::clean_text(($item['excerptOriginal'] ?? '') ?: ($item['excerpt'] ?? '')),
            'excerptOriginal' => self::clean_text(($item['excerptOriginal'] ?? '') ?: ($item['excerpt'] ?? '')),
            'image' => esc_url_raw((string) ($item['image'] ?? '')),
            'mediaType' => sanitize_key((string) ($item['mediaType'] ?? 'image')),
            'photographer' => sanitize_text_field((string) ($item['photographer'] ?? 'NASA')),
            'sourceUrl' => esc_url_raw((string) ($item['sourceUrl'] ?? '')),
            'mediaUrl' => esc_url_raw((string) ($item['mediaUrl'] ?? ($item['sourceUrl'] ?? ''))),
            'hdUrl' => esc_url_raw((string) ($item['hdUrl'] ?? '')),
            'thumbnailUrl' => esc_url_raw((string) ($item['thumbnailUrl'] ?? '')),
            'serviceVersion' => sanitize_text_field((string) ($item['serviceVersion'] ?? '')),
            'sourceHash' => self::source_hash($item),
        );
    }

    public static function source_hash(array $item)
    {
        $source = array(
            'date' => self::sanitize_date($item['date'] ?? ''),
            'titleOriginal' => self::normalize_for_hash(($item['titleOriginal'] ?? '') ?: ($item['title'] ?? '')),
            'contentOriginal' => self::normalize_for_hash(($item['contentOriginal'] ?? '') ?: ($item['content'] ?? '')),
            'mediaType' => sanitize_key((string) ($item['mediaType'] ?? 'image')),
            'photographer' => self::normalize_for_hash($item['photographer'] ?? ''),
            'sourceUrl' => esc_url_raw((string) ($item['sourceUrl'] ?? '')),
            'hdUrl' => esc_url_raw((string) ($item['hdUrl'] ?? '')),
            'serviceVersion' => sanitize_text_field((string) ($item['serviceVersion'] ?? '')),
        );
        return hash('sha256', wp_json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function mark_stale($post_id, $new_source_hash = '')
    {
        $post_id = (int) $post_id;
        if (!$post_id) {
            return false;
        }
        if ($new_source_hash) {
            update_post_meta($post_id, self::META_SOURCE_HASH, self::sanitize_hash($new_source_hash));
        }
        update_post_meta($post_id, self::META_STATUS, self::STATUS_STALE);
        return true;
    }

    private static function sanitize_date($date)
    {
        $date = sanitize_text_field((string) $date);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
    }

    private static function clean_text($value)
    {
        return html_entity_decode(wp_strip_all_tags((string) $value), ENT_QUOTES, 'UTF-8');
    }

    private static function normalize_for_hash($value)
    {
        $value = html_entity_decode(wp_strip_all_tags((string) $value), ENT_QUOTES, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value));
        return $value === null ? '' : $value;
    }

    private static function sanitize_hash($hash)
    {
        $hash = strtolower(sanitize_text_field((string) $hash));
        return preg_match('/^[a-f0-9]{64}$/', $hash) ? $hash : '';
    }
}
