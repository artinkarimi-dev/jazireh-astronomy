<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Daily
{
    const POST_TYPE = 'jazireh_daily';
    const OPTION_SYNC_SECRET = 'jazireh_daily_sync_secret';
    const OPTION_SYNC_STATUS = 'jazireh_daily_sync_status';
    const META_SOURCE_TYPE = '_jazireh_daily_source_type';
    const META_SOURCE_POST_ID = '_jazireh_daily_source_post_id';
    const META_SOURCE_URL = '_jazireh_daily_source_url';
    const META_SOURCE_PUBLISHED_AT = '_jazireh_daily_source_published_at';
    const META_SOURCE_PUBLISHED_LABEL = '_jazireh_daily_source_published_label';
    const META_SYNCED_AT = '_jazireh_daily_synced_at';
    const META_SOURCE_HASH = '_jazireh_daily_source_hash';
    const META_SYNC_STATUS = '_jazireh_daily_sync_status';
    const META_IMAGE_IDS = '_jazireh_daily_image_ids';
    const META_FREEZE_UPDATES = '_jazireh_daily_freeze_updates';
    const SYNC_WINDOW = 300;
    const MAX_PAYLOAD_BYTES = 1048576;
    const MAX_POSTS_PER_SYNC = 10;
    const MAX_IMAGES_PER_POST = 4;
    const MAX_IMAGE_BYTES = 8388608;

    private static $sync_context = false;

    public static function boot()
    {
        add_action('init', array(__CLASS__, 'register_content_type'));
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'save_meta'));
    }

    public static function register_content_type()
    {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => 'جزیره دیلی',
                'singular_name' => 'پست روزانه جزیره',
                'menu_name' => 'جزیره دیلی',
                'add_new' => 'افزودن پست',
                'add_new_item' => 'افزودن پست روزانه',
                'edit_item' => 'ویرایش پست روزانه',
                'new_item' => 'پست روزانه جدید',
                'view_item' => 'مشاهده پست روزانه',
                'search_items' => 'جستجو در پست‌های روزانه',
                'not_found' => 'پست روزانه‌ای پیدا نشد',
                'all_items' => 'همه پست‌های روزانه',
            ),
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-format-image',
            'menu_position' => 7,
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions'),
            'has_archive' => false,
            'rewrite' => false,
            'show_in_nav_menus' => false,
            'map_meta_cap' => true,
            'capability_type' => 'post',
        ));
    }

    public static function sync_secret()
    {
        $stored = get_option(self::OPTION_SYNC_SECRET, '');
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        $secret = wp_generate_password(64, true, true);
        update_option(self::OPTION_SYNC_SECRET, $secret, false);
        return $secret;
    }

    public static function sync_status()
    {
        $status = get_option(self::OPTION_SYNC_STATUS, array());
        return is_array($status) ? $status : array();
    }

    public static function latest_posts($limit = 6)
    {
        $query = new WP_Query(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => min(max((int) $limit, 1), 24),
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ));

        return array_map(array(__CLASS__, 'format_post'), $query->posts);
    }

    public static function find_by_slug($slug)
    {
        $posts = get_posts(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'name' => sanitize_title($slug),
            'posts_per_page' => 1,
        ));

        return !empty($posts[0]) ? self::format_post($posts[0]) : null;
    }

    public static function sync_posts($payload)
    {
        if (!is_array($payload) || !isset($payload['posts']) || !is_array($payload['posts'])) {
            return new WP_Error('jazireh_daily_invalid_payload', 'درخواست همگام‌سازی نامعتبر است.', array('status' => 400));
        }

        $posts = array_slice($payload['posts'], 0, self::MAX_POSTS_PER_SYNC);
        $stats = array(
            'attempted_at' => current_time('mysql', 1),
            'last_attempt' => current_time('mysql', 1),
            'last_success' => '',
            'last_error' => '',
            'latest_source_post_id' => '',
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        );

        self::$sync_context = true;
        try {
            foreach ($posts as $index => $item) {
                $prepared = self::prepare_sync_item($item);
                if (is_wp_error($prepared)) {
                    $stats['last_error'] = $prepared->get_error_message();
                    continue;
                }
                if ($index === 0) {
                    $stats['latest_source_post_id'] = $prepared['sourcePostId'];
                }

                $result = self::upsert_post($prepared);
                if ($result === 'created') {
                    $stats['created']++;
                } elseif ($result === 'updated') {
                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
            }
        } finally {
            self::$sync_context = false;
        }

        $stats['last_success'] = current_time('mysql', 1);
        update_option(self::OPTION_SYNC_STATUS, $stats, false);

        return $stats;
    }

    public static function ingest_request(WP_REST_Request $request)
    {
        $auth = self::authorize_sync_request($request);
        if (is_wp_error($auth)) {
            return $auth;
        }

        $payload = $request->get_json_params();
        $result = self::sync_posts(is_array($payload) ? $payload : array());
        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $result,
        ), 200);
    }

    public static function authorize_sync_request(WP_REST_Request $request)
    {
        $secret = self::sync_secret();
        if ($secret === '') {
            return new WP_Error('jazireh_daily_sync_unavailable', 'کلید همگام‌سازی روزانه تنظیم نشده است.', array('status' => 503));
        }

        $timestamp = (string) $request->get_header('x-jazireh-timestamp');
        $signature = (string) $request->get_header('x-jazireh-signature');
        if ($timestamp === '' || $signature === '') {
            return new WP_Error('jazireh_daily_sync_unauthorized', 'دسترسی غیرمجاز.', array('status' => 403));
        }

        $timestamp_int = absint($timestamp);
        if (!$timestamp_int || abs(time() - $timestamp_int) > self::SYNC_WINDOW) {
            return new WP_Error('jazireh_daily_sync_expired', 'درخواست همگام‌سازی منقضی شده است.', array('status' => 403));
        }

        $body = (string) $request->get_body();
        if (strlen($body) > self::MAX_PAYLOAD_BYTES) {
            return new WP_Error('jazireh_daily_sync_too_large', 'حجم درخواست بیش از حد مجاز است.', array('status' => 413));
        }

        $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
        if (!hash_equals($expected, $signature)) {
            return new WP_Error('jazireh_daily_sync_signature_invalid', 'امضای درخواست نامعتبر است.', array('status' => 403));
        }

        $replay_key = 'jazireh_daily_sync_' . md5($signature . '|' . $timestamp);
        if (get_transient($replay_key)) {
            return new WP_Error('jazireh_daily_sync_replay', 'این درخواست قبلا استفاده شده است.', array('status' => 409));
        }
        set_transient($replay_key, 1, self::SYNC_WINDOW);

        return true;
    }

    public static function format_post(WP_Post $post)
    {
        $image_ids = self::image_ids($post->ID);
        if (empty($image_ids) && has_post_thumbnail($post)) {
            $image_ids = array((int) get_post_thumbnail_id($post));
        }
        $images = array();
        foreach ($image_ids as $image_id) {
            $image = wp_get_attachment_image_src($image_id, 'full');
            if (!$image) {
                continue;
            }
            $images[] = array(
                'id' => (int) $image_id,
                'url' => esc_url_raw($image[0]),
                'width' => isset($image[1]) ? (int) $image[1] : 0,
                'height' => isset($image[2]) ? (int) $image[2] : 0,
                'alt' => (string) get_post_meta($image_id, '_wp_attachment_image_alt', true),
            );
        }

        $media = self::media_payload($post, $images);

        return array(
            'id' => (int) $post->ID,
            'slug' => $post->post_name,
            'title' => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
            'text' => self::plain_text($post),
            'excerpt' => (string) ($post->post_excerpt ?: wp_trim_words(self::plain_text($post), 28, '...')),
            'source' => (string) get_post_meta($post->ID, self::META_SOURCE_TYPE, true) ?: 'wordpress-manual',
            'sourceUrl' => (string) get_post_meta($post->ID, self::META_SOURCE_URL, true),
            'publishedAt' => (string) get_post_meta($post->ID, self::META_SOURCE_PUBLISHED_AT, true) ?: get_post_time(DATE_ATOM, true, $post),
            'publishedLabel' => (string) get_post_meta($post->ID, self::META_SOURCE_PUBLISHED_LABEL, true),
            'images' => $images,
            'media' => $media,
        );
    }

    private static function media_payload(WP_Post $post, array $images)
    {
        $source_url = (string) get_post_meta($post->ID, self::META_SOURCE_URL, true);
        if (!empty($images[0]['url'])) {
            return array(
                'type' => 'image',
                'url' => $images[0]['url'],
                'thumbnail' => $images[0]['url'],
                'width' => (int) ($images[0]['width'] ?? 0),
                'height' => (int) ($images[0]['height'] ?? 0),
                'alt' => (string) ($images[0]['alt'] ?? ''),
                'sourceUrl' => esc_url_raw($source_url),
            );
        }

        $youtube_id = self::youtube_id($source_url);
        if ($youtube_id) {
            $is_short = strpos($source_url, '/shorts/') !== false;
            return array(
                'type' => $is_short ? 'youtube-short' : 'youtube-video',
                'url' => esc_url_raw($source_url),
                'thumbnail' => 'https://i.ytimg.com/vi/' . rawurlencode($youtube_id) . '/hqdefault.jpg',
                'youtubeId' => $youtube_id,
                'aspectRatio' => $is_short ? '9/16' : '16/9',
                'sourceUrl' => esc_url_raw($source_url),
            );
        }

        return array(
            'type' => 'none',
            'url' => '',
            'thumbnail' => '',
            'sourceUrl' => esc_url_raw($source_url),
        );
    }

    private static function youtube_id($url)
    {
        $url = (string) $url;
        if ($url === '') {
            return '';
        }

        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $query = (string) wp_parse_url($url, PHP_URL_QUERY);

        if (strpos($host, 'youtu.be') !== false) {
            return sanitize_text_field(trim($path, '/'));
        }
        if (strpos($host, 'youtube.com') === false) {
            return '';
        }
        if (preg_match('#/(?:shorts|embed)/([a-zA-Z0-9_-]{6,})#', $path, $matches)) {
            return sanitize_text_field($matches[1]);
        }
        parse_str($query, $params);
        return !empty($params['v']) ? sanitize_text_field((string) $params['v']) : '';
    }

    public static function integrations_panel()
    {
        $status = self::sync_status();
        $secret = self::sync_secret();
        $masked = substr($secret, 0, 8) . '…' . substr($secret, -8);
        ?>
        <div class="jazireh-settings-card">
            <h2>Jazireh Daily / Community Sync</h2>
            <p>همگام‌سازی خودکار پست‌های کامیونیتی یوتیوب خارج از درخواست‌های عمومی سایت اجرا می‌شود و وردپرس بعد از دریافت، منبع پایدار نمایش است.</p>
            <div class="jazireh-settings-grid two-col">
                <div class="jazireh-field"><label>Last successful sync</label><input type="text" readonly class="regular-text" value="<?php echo esc_attr($status['last_success'] ?? ''); ?>"></div>
                <div class="jazireh-field"><label>Last attempt</label><input type="text" readonly class="regular-text" value="<?php echo esc_attr($status['last_attempt'] ?? ''); ?>"></div>
                <div class="jazireh-field"><label>Latest source post ID</label><input type="text" readonly class="regular-text" value="<?php echo esc_attr($status['latest_source_post_id'] ?? ''); ?>"></div>
                <div class="jazireh-field"><label>Created / Updated / Skipped</label><input type="text" readonly class="regular-text" value="<?php echo esc_attr(sprintf('%d / %d / %d', (int) ($status['created'] ?? 0), (int) ($status['updated'] ?? 0), (int) ($status['skipped'] ?? 0))); ?>"></div>
                <div class="jazireh-field"><label>Sync secret</label><input type="text" readonly class="regular-text code" value="<?php echo esc_attr($masked); ?>"></div>
                <div class="jazireh-field"><label>Last error</label><textarea rows="3" readonly><?php echo esc_textarea($status['last_error'] ?? ''); ?></textarea></div>
            </div>
        </div>
        <?php
    }

    public static function add_meta_boxes()
    {
        add_meta_box('jazireh_daily_sync', 'اطلاعات همگام‌سازی جزیره دیلی', array(__CLASS__, 'render_meta_box'), self::POST_TYPE, 'side', 'high');
    }

    public static function render_meta_box($post)
    {
        wp_nonce_field('jazireh_daily_save', 'jazireh_daily_nonce');
        ?>
        <p><strong>منبع:</strong> <?php echo esc_html(get_post_meta($post->ID, self::META_SOURCE_TYPE, true) ?: 'wordpress-manual'); ?></p>
        <p><strong>Source Post ID:</strong> <?php echo esc_html(get_post_meta($post->ID, self::META_SOURCE_POST_ID, true) ?: '—'); ?></p>
        <p><strong>Source URL:</strong> <?php $url = (string) get_post_meta($post->ID, self::META_SOURCE_URL, true); if ($url) : ?><a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($url); ?></a><?php else : ?>—<?php endif; ?></p>
        <p><strong>Source Published:</strong> <?php echo esc_html(get_post_meta($post->ID, self::META_SOURCE_PUBLISHED_LABEL, true) ?: get_post_meta($post->ID, self::META_SOURCE_PUBLISHED_AT, true) ?: '—'); ?></p>
        <p><strong>Last Sync:</strong> <?php echo esc_html(get_post_meta($post->ID, self::META_SYNCED_AT, true) ?: '—'); ?></p>
        <p>
            <label>
                <input type="checkbox" name="jazireh_daily_freeze_updates" value="1" <?php checked(get_post_meta($post->ID, self::META_FREEZE_UPDATES, true), '1'); ?>>
                محتوای این پست از به‌روزرسانی خودکار محافظت شود
            </label>
        </p>
        <p style="color:#646970;line-height:1.8">اگر این گزینه فعال باشد، همگام‌سازی فقط وضعیت و فراداده منبع را به‌روز می‌کند و عنوان/متن/تصویر اصلی را بازنویسی نمی‌کند.</p>
        <?php
    }

    public static function save_meta($post_id)
    {
        if (self::$sync_context) {
            return;
        }
        if (!isset($_POST['jazireh_daily_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jazireh_daily_nonce'])), 'jazireh_daily_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        update_post_meta($post_id, self::META_FREEZE_UPDATES, isset($_POST['jazireh_daily_freeze_updates']) ? '1' : '0');
    }

    private static function prepare_sync_item($item)
    {
        if (!is_array($item)) {
            return new WP_Error('jazireh_daily_invalid_item', 'هر آیتم همگام‌سازی باید ساختار معتبر داشته باشد.', array('status' => 400));
        }

        $source_post_id = sanitize_text_field((string) ($item['sourcePostId'] ?? ''));
        $source_url = esc_url_raw((string) ($item['sourceUrl'] ?? ''));
        $title = sanitize_text_field((string) ($item['title'] ?? ''));
        $text = self::normalize_text((string) ($item['text'] ?? ''));
        $source_hash = sanitize_text_field((string) ($item['sourceHash'] ?? md5(wp_json_encode($item))));
        $images = is_array($item['images'] ?? null) ? array_slice($item['images'], 0, self::MAX_IMAGES_PER_POST) : array();

        if ($source_post_id === '' || $source_url === '' || $title === '' || $text === '') {
            return new WP_Error('jazireh_daily_missing_fields', 'فیلدهای ضروری پست روزانه کامل نیستند.', array('status' => 400));
        }

        return array(
            'sourceType' => sanitize_text_field((string) ($item['source'] ?? 'youtube-community')),
            'sourcePostId' => $source_post_id,
            'sourceUrl' => $source_url,
            'sourcePublishedAt' => sanitize_text_field((string) ($item['publishedAt'] ?? '')),
            'sourcePublishedLabel' => sanitize_text_field((string) ($item['publishedLabel'] ?? '')),
            'title' => $title,
            'text' => $text,
            'excerpt' => sanitize_textarea_field((string) ($item['excerpt'] ?? wp_trim_words($text, 28, '...'))),
            'sourceHash' => $source_hash,
            'images' => $images,
        );
    }

    private static function upsert_post($item)
    {
        $existing = self::find_existing_post($item['sourcePostId']);
        if ($existing && get_post_meta($existing->ID, self::META_SOURCE_HASH, true) === $item['sourceHash']) {
            update_post_meta($existing->ID, self::META_SYNCED_AT, current_time('mysql', 1));
            update_post_meta($existing->ID, self::META_SYNC_STATUS, 'unchanged');
            return 'skipped';
        }

        $image_ids = self::sync_images($item['sourcePostId'], $item['images']);
        $freeze = $existing ? get_post_meta($existing->ID, self::META_FREEZE_UPDATES, true) === '1' : false;
        $postarr = array(
            'post_type' => self::POST_TYPE,
            'post_status' => $existing ? $existing->post_status : 'publish',
            'post_name' => $existing ? $existing->post_name : sanitize_title($item['title'] . '-' . $item['sourcePostId']),
        );

        if (!$freeze) {
            $postarr['post_title'] = $item['title'];
            $postarr['post_excerpt'] = $item['excerpt'];
            $postarr['post_content'] = $item['text'];
        }

        if ($existing) {
            $postarr['ID'] = (int) $existing->ID;
            $post_id = wp_update_post($postarr, true);
        } else {
            $post_id = wp_insert_post($postarr, true);
        }

        if (is_wp_error($post_id)) {
            return 'skipped';
        }

        update_post_meta($post_id, self::META_SOURCE_TYPE, $item['sourceType']);
        update_post_meta($post_id, self::META_SOURCE_POST_ID, $item['sourcePostId']);
        update_post_meta($post_id, self::META_SOURCE_URL, $item['sourceUrl']);
        update_post_meta($post_id, self::META_SOURCE_PUBLISHED_AT, $item['sourcePublishedAt']);
        update_post_meta($post_id, self::META_SOURCE_PUBLISHED_LABEL, $item['sourcePublishedLabel']);
        update_post_meta($post_id, self::META_SYNCED_AT, current_time('mysql', 1));
        update_post_meta($post_id, self::META_SOURCE_HASH, $item['sourceHash']);
        update_post_meta($post_id, self::META_SYNC_STATUS, 'available');
        update_post_meta($post_id, self::META_IMAGE_IDS, array_values($image_ids));

        if (!empty($image_ids[0])) {
            set_post_thumbnail($post_id, $image_ids[0]);
        }

        return $existing ? 'updated' : 'created';
    }

    private static function find_existing_post($source_post_id)
    {
        $query = new WP_Query(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => self::META_SOURCE_POST_ID,
                    'value' => $source_post_id,
                ),
            ),
        ));

        return !empty($query->posts[0]) ? $query->posts[0] : null;
    }

    private static function sync_images($source_post_id, array $images)
    {
        $ids = array();
        foreach ($images as $index => $image) {
            $attachment_id = self::sync_single_image($source_post_id, $index, $image);
            if ($attachment_id) {
                $ids[] = $attachment_id;
            }
        }
        return $ids;
    }

    private static function sync_single_image($source_post_id, $index, $image)
    {
        if (!is_array($image) || (empty($image['url']) && empty($image['dataBase64']))) {
            return 0;
        }

        $url = esc_url_raw((string) ($image['url'] ?? ''));
        if ($url !== '' && !self::allowed_remote_image($url)) {
            return 0;
        }

        $identity = sanitize_key($source_post_id . '-' . $index);
        $hash = sanitize_text_field((string) ($image['hash'] ?? md5($url)));
        $query = new WP_Query(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_jazireh_daily_image_key',
                    'value' => $identity,
                ),
            ),
            'fields' => 'ids',
        ));
        $existing_id = !empty($query->posts[0]) ? (int) $query->posts[0] : 0;
        if ($existing_id && get_post_meta($existing_id, '_jazireh_daily_image_hash', true) === $hash) {
            return $existing_id;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        if (!empty($image['dataBase64']) && !empty($image['mimeType'])) {
            $attachment_id = self::store_inline_image($source_post_id, $index, $image, $existing_id, $identity, $hash);
            if ($attachment_id) {
                return $attachment_id;
            }
        }

        $response = wp_safe_remote_get($url, array(
            'timeout' => 15,
            'limit_response_size' => self::MAX_IMAGE_BYTES,
            'reject_unsafe_urls' => true,
        ));
        if (is_wp_error($response)) {
            return $existing_id;
        }

        $content_type = strtolower((string) wp_remote_retrieve_header($response, 'content-type'));
        if (strpos($content_type, 'image/') !== 0 || !preg_match('#^image/(jpeg|jpg|png|webp|gif|avif)$#', $content_type)) {
            return $existing_id;
        }

        $body = wp_remote_retrieve_body($response);
        if ($body === '' || strlen($body) > self::MAX_IMAGE_BYTES) {
            return $existing_id;
        }

        $tmp = wp_tempnam($url);
        if (!$tmp) {
            return $existing_id;
        }
        file_put_contents($tmp, $body);
        $image_info = @getimagesize($tmp);
        if (!$image_info) {
            @unlink($tmp);
            return $existing_id;
        }

        $filename = sanitize_file_name($source_post_id . '-' . ($index + 1) . '.' . image_type_to_extension($image_info[2], false));
        $file = array(
            'name' => $filename,
            'tmp_name' => $tmp,
        );
        $attachment_id = media_handle_sideload($file, 0);
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return $existing_id;
        }

        update_post_meta($attachment_id, '_jazireh_daily_image_key', $identity);
        update_post_meta($attachment_id, '_jazireh_daily_image_hash', $hash);
        update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field((string) ($image['alt'] ?? '')));

        if ($existing_id && $existing_id !== $attachment_id) {
            wp_delete_attachment($existing_id, true);
        }

        return (int) $attachment_id;
    }

    private static function store_inline_image($source_post_id, $index, $image, $existing_id, $identity, $hash)
    {
        $content_type = strtolower((string) ($image['mimeType'] ?? ''));
        if (strpos($content_type, 'image/') !== 0 || !preg_match('#^image/(jpeg|jpg|png|webp|gif|avif)$#', $content_type)) {
            return 0;
        }

        $body = base64_decode((string) $image['dataBase64'], true);
        if ($body === false || $body === '' || strlen($body) > self::MAX_IMAGE_BYTES) {
            return 0;
        }

        $tmp = wp_tempnam((string) ($image['filename'] ?? $source_post_id));
        if (!$tmp) {
            return 0;
        }

        file_put_contents($tmp, $body);
        $image_info = @getimagesize($tmp);
        if (!$image_info) {
            @unlink($tmp);
            return 0;
        }

        $extension = image_type_to_extension($image_info[2], false);
        $filename = sanitize_file_name((string) ($image['filename'] ?? ($source_post_id . '-' . ($index + 1) . '.' . $extension)));
        if ($filename === '') {
            $filename = sanitize_file_name($source_post_id . '-' . ($index + 1) . '.' . $extension);
        }

        $file = array(
            'name' => $filename,
            'tmp_name' => $tmp,
        );
        $attachment_id = media_handle_sideload($file, 0);
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return 0;
        }

        update_post_meta($attachment_id, '_jazireh_daily_image_key', $identity);
        update_post_meta($attachment_id, '_jazireh_daily_image_hash', $hash);
        update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field((string) ($image['alt'] ?? '')));

        if ($existing_id && $existing_id !== $attachment_id) {
            wp_delete_attachment($existing_id, true);
        }

        return (int) $attachment_id;
    }

    private static function image_ids($post_id)
    {
        $ids = get_post_meta($post_id, self::META_IMAGE_IDS, true);
        return is_array($ids) ? array_values(array_filter(array_map('absint', $ids))) : array();
    }

    private static function plain_text(WP_Post $post)
    {
        $content = (string) $post->post_content;
        $content = preg_replace("/\r\n|\r/", "\n", $content);
        return trim(wp_strip_all_tags($content, true));
    }

    private static function normalize_text($text)
    {
        $text = str_replace(array("\r\n", "\r"), "\n", wp_unslash((string) $text));
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    private static function allowed_remote_image($url)
    {
        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        $allowed = array(
            'googleusercontent.com',
            'ggpht.com',
            'ytimg.com',
            'youtube.com',
        );

        foreach ($allowed as $suffix) {
            if ($host === $suffix || substr($host, -strlen('.' . $suffix)) === '.' . $suffix) {
                return true;
            }
        }

        return false;
    }
}
