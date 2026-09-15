<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Events
{
    const POST_TYPE = 'jazireh_event';
    const META_CATEGORY = '_jazireh_event_category';
    const META_TYPE = '_jazireh_event_type';
    const META_START_DATE = '_jazireh_event_start_date';
    const META_END_DATE = '_jazireh_event_end_date';
    const META_PEAK_DATE = '_jazireh_event_peak_date';
    const META_BEST_TIME = '_jazireh_event_best_time';
    const META_VISIBILITY_SCORE = '_jazireh_event_visibility_score';
    const META_VISIBILITY = '_jazireh_event_visibility';
    const META_SOURCE_LABEL = '_jazireh_event_source_label';
    const META_DIRECTION = '_jazireh_event_direction';
    const META_SOURCE_URL = '_jazireh_event_source_url';
    const META_FEATURED = '_jazireh_event_featured';

    private const SYNODIC_MONTH = 29.530588853;
    private const KNOWN_NEW_MOON_JD = 2451550.1;

    public static function boot()
    {
        add_action('init', array(__CLASS__, 'register_content_type'));
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'save_meta'));
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array(__CLASS__, 'columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array(__CLASS__, 'column_content'), 10, 2);
        add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', array(__CLASS__, 'sortable_columns'));
        add_action('pre_get_posts', array(__CLASS__, 'sort_admin_columns'));
    }

    public static function register_content_type()
    {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => 'رویدادهای نجومی',
                'singular_name' => 'رویداد نجومی',
                'menu_name' => 'رویدادهای نجومی',
                'add_new' => 'افزودن رویداد',
                'add_new_item' => 'افزودن رویداد نجومی',
                'edit_item' => 'ویرایش رویداد',
                'new_item' => 'رویداد جدید',
                'view_item' => 'مشاهده رویداد',
                'search_items' => 'جستجو در رویدادها',
                'not_found' => 'رویدادی پیدا نشد',
                'all_items' => 'همه رویدادها',
            ),
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'menu_position' => 8,
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'),
            'has_archive' => false,
            'rewrite' => false,
            'show_in_nav_menus' => false,
            'map_meta_cap' => true,
            'capability_type' => 'post',
        ));
    }

    public static function event_types()
    {
        return array_merge(self::categories(), array(
            'eclipse' => 'گرفتگی',
            'conjunction' => 'مقارنه',
            'opposition' => 'مقابله',
            'supermoon' => 'ابرماه',
            'other' => 'رویداد نجومی دیگر',
        ));
    }

    public static function payload($limit = 12, $days = 90, $planet_payload = null)
    {
        $now = current_time('timestamp', true);
        $cache_key = self::payload_cache_key($limit, $days, $now, $planet_payload);
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $items = array_merge(
            self::generated_events($now, $days, $planet_payload),
            self::curated_events($limit, $now, $days)
        );

        usort($items, array(__CLASS__, 'sort_events'));
        $items = array_slice($items, 0, min(max((int) $limit, 1), 24));

        $includes_planet_events = is_array($planet_payload);

        $payload = array(
            'date' => self::date($now),
            'timezone' => wp_timezone_string(),
            'status' => 'ready',
            'accuracy' => $includes_planet_events ? 'mixed' : 'estimated',
            'confidence' => 'medium',
            'source' => $includes_planet_events ? 'jpl-horizons,wordpress-curated,curated-static' : 'moon-service,wordpress-curated,curated-static',
            'calculatedAt' => wp_date(DATE_ATOM, $now),
            'generatedAtUtc' => gmdate(DATE_ATOM, $now),
            'location' => self::location_meta(),
            'isFallback' => false,
            'fallbackReason' => '',
            'displayWarning' => $includes_planet_events ? 'رویدادهای محاسباتی از JPL Horizons ساخته می‌شوند؛ رویدادهای تقویمی و وردپرسی باید با منبع معتبر بررسی شوند.' : 'رویدادهای محاسباتی سبک از سرویس ماه و تقویم‌های شناخته‌شده ساخته می‌شوند؛ رویدادهای سیاره‌ای فقط با داده سیاره‌ای معتبر اضافه می‌شوند.',
            'items' => $items,
            'message' => $includes_planet_events ? 'رویدادها ترکیبی از محاسبات JPL Horizons، فرصت‌های رصدی واقعی، و رویدادهای مدیریت‌شده یا تقویمی هستند.' : 'رویدادها از داده‌های سبک ماه، تقویم‌های شناخته‌شده، و رویدادهای مدیریت‌شده وردپرس ساخته می‌شوند.',
            'generatedAt' => wp_date(DATE_ATOM, $now),
        );

        set_transient($cache_key, $payload, self::payload_cache_ttl($payload));

        return $payload;
    }

    public static function today($limit = 6)
    {
        $now = current_time('timestamp', true);
        $today = self::date($now);
        $all = self::payload(18, 45);
        $items = array();

        foreach ($all['items'] as $event) {
            $start = (string) ($event['startDate'] ?? '');
            $peak = (string) ($event['peakDate'] ?? '');
            $end = (string) ($event['endDate'] ?? $start);
            if ($start <= $today && $end >= $today) {
                $items[] = $event;
                continue;
            }
            if ($peak === $today) {
                $items[] = $event;
            }
        }

        if (empty($items)) {
            $items = array_slice($all['items'], 0, min(max((int) $limit, 1), 8));
        }

        $all['items'] = array_slice($items, 0, min(max((int) $limit, 1), 8));
        $all['message'] = 'رویدادهای مرتبط با امروز یا نزدیک‌ترین فرصت‌های رصدی پیش رو نمایش داده می‌شوند.';
        return $all;
    }

    public static function tonight_highlights($sky, $planets, $events)
    {
        $planet_items = is_array($planets) && !empty($planets['items']) ? $planets['items'] : array();
        $best_planet = !empty($planet_items[0]) ? $planet_items[0] : null;
        $event_items = is_array($events) && !empty($events['items']) ? $events['items'] : array();
        $best_event = !empty($event_items[0]) ? $event_items[0] : null;
        $illumination = isset($sky['moonIllumination']) ? (float) $sky['moonIllumination'] : 0;

        return array(
            array(
                'id' => 'best-window',
                'title' => 'بهترین پنجره رصد',
                'value' => (string) ($sky['bestTime'] ?? 'امشب'),
                'summary' => 'این بازه براساس غروب خورشید و روشنایی ماه پیشنهاد می‌شود.',
                'source' => 'generated',
                'confidence' => 'estimated',
            ),
            array(
                'id' => 'best-planet',
                'title' => 'بهترین سیاره',
                'value' => $best_planet ? $best_planet['name'] . ' - ' . $best_planet['statusLabel'] : 'داده در دسترس نیست',
                'summary' => $best_planet ? $best_planet['summary'] : 'فعلا سیاره شاخصی برای نمایش پیدا نشد.',
                'source' => 'generated',
                'confidence' => 'estimated',
            ),
            array(
                'id' => 'moon-interference',
                'title' => 'اثر نور ماه',
                'value' => self::moon_interference_label($illumination),
                'summary' => 'روشنایی ماه حدود ' . Jazireh_Astronomy::persian_digits(number_format($illumination, 1, '.', '')) . ' درصد است.',
                'source' => 'generated',
                'confidence' => 'estimated',
            ),
            array(
                'id' => 'next-event',
                'title' => 'رویداد بعدی',
                'value' => $best_event ? $best_event['title'] : 'رویدادی ثبت نشده',
                'summary' => $best_event ? $best_event['summary'] : 'بعد از ثبت رویدادها در وردپرس، این بخش کامل‌تر می‌شود.',
                'source' => $best_event ? $best_event['source'] : 'fallback',
                'confidence' => $best_event ? $best_event['confidence'] : 'estimated',
            ),
        );
    }

    public static function add_meta_boxes()
    {
        add_meta_box('jazireh_event_details', 'جزئیات رویداد نجومی', array(__CLASS__, 'render_meta_box'), self::POST_TYPE, 'side', 'high');
    }

    public static function render_meta_box($post)
    {
        wp_nonce_field('jazireh_event_save', 'jazireh_event_nonce');
        $category = get_post_meta($post->ID, self::META_CATEGORY, true) ?: 'observing_tip';
        $type = get_post_meta($post->ID, self::META_TYPE, true) ?: $category;
        ?>
        <p><label for="jazireh_event_type"><strong>نوع رویداد</strong></label>
            <select id="jazireh_event_type" name="jazireh_event_type" class="widefat">
                <?php foreach (self::event_types() as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($type, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p><label for="jazireh_event_category"><strong>دسته‌بندی</strong></label>
            <select id="jazireh_event_category" name="jazireh_event_category" class="widefat">
                <?php foreach (self::categories() as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($category, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php self::field($post->ID, self::META_START_DATE, 'تاریخ شروع', 'date'); ?>
        <?php self::field($post->ID, self::META_PEAK_DATE, 'زمان اوج', 'datetime-local'); ?>
        <p style="margin-top:-8px;color:#646970;line-height:1.7">زمان اوج می‌تواند زمان دقیق یا تقریبی بیشترین اهمیت رویداد باشد.</p>
        <?php self::field($post->ID, self::META_END_DATE, 'تاریخ پایان', 'date'); ?>
        <?php self::field($post->ID, self::META_BEST_TIME, 'بهترین زمان رصد', 'text', 'مثلا پس از نیمه‌شب'); ?>
        <?php self::field($post->ID, self::META_DIRECTION, 'جهت', 'text', 'مثلا شرق، جنوب، غرب'); ?>
        <?php self::field($post->ID, self::META_VISIBILITY_SCORE, 'امتیاز دیدپذیری', 'number', '۰ تا ۱۰۰'); ?>
        <p style="margin-top:-8px;color:#646970;line-height:1.7">امتیاز ۰ تا ۱۰۰ فقط یک راهنمای عمومی برای شرایط رصد است.</p>
        <p>
            <label for="jazireh_event_visibility"><strong>شرایط مشاهده / مکان</strong></label>
            <textarea id="jazireh_event_visibility" name="jazireh_event_visibility" rows="3" class="widefat" placeholder="مثلاً قابل مشاهده از ایران، بهترین زمان پس از نیمه‌شب..."><?php echo esc_textarea(get_post_meta($post->ID, self::META_VISIBILITY, true)); ?></textarea>
        </p>
        <?php self::field($post->ID, self::META_SOURCE_LABEL, 'عنوان منبع', 'text', 'NASA, Time and Date, IAU...'); ?>
        <?php self::field($post->ID, self::META_SOURCE_URL, 'لینک منبع', 'url'); ?>
        <p><label><input type="checkbox" name="jazireh_event_featured" value="1" <?php checked(get_post_meta($post->ID, self::META_FEATURED, true), '1'); ?>> رویداد ویژه</label></p>
        <p style="color:#646970;line-height:1.8">عنوان، توضیح کوتاه و متن رویداد را از بخش‌های اصلی همین صفحه وارد کنید.</p>
        <?php
    }

    public static function save_meta($post_id)
    {
        if (!isset($_POST['jazireh_event_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jazireh_event_nonce'])), 'jazireh_event_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $category = sanitize_key((string) ($_POST['jazireh_event_category'] ?? 'observing_tip'));
        $type = sanitize_key((string) ($_POST['jazireh_event_type'] ?? $category));
        if (!array_key_exists($category, self::categories())) {
            $category = 'observing_tip';
        }
        if (!array_key_exists($type, self::event_types())) {
            $type = $category;
        }
        update_post_meta($post_id, self::META_CATEGORY, $category);
        update_post_meta($post_id, self::META_TYPE, $type);
        $start = self::valid_date(wp_unslash((string) ($_POST['jazireh_event_start_date'] ?? '')));
        $end = self::valid_date(wp_unslash((string) ($_POST['jazireh_event_end_date'] ?? '')));
        $peak = self::valid_datetime(wp_unslash((string) ($_POST['jazireh_event_peak_date'] ?? '')));
        if ($start !== '' && $end !== '' && $end < $start) {
            $end = $start;
        }
        update_post_meta($post_id, self::META_START_DATE, $start);
        update_post_meta($post_id, self::META_END_DATE, $end);
        update_post_meta($post_id, self::META_PEAK_DATE, $peak);
        foreach (array(self::META_BEST_TIME, self::META_DIRECTION) as $key) {
            $field = str_replace('_jazireh_event_', 'jazireh_event_', $key);
            update_post_meta($post_id, $key, sanitize_text_field(wp_unslash((string) ($_POST[$field] ?? ''))));
        }
        $score = isset($_POST['jazireh_event_visibility_score']) ? absint($_POST['jazireh_event_visibility_score']) : 70;
        update_post_meta($post_id, self::META_VISIBILITY_SCORE, (string) min(max($score, 0), 100));
        update_post_meta($post_id, self::META_VISIBILITY, sanitize_textarea_field(wp_unslash((string) ($_POST['jazireh_event_visibility'] ?? ''))));
        update_post_meta($post_id, self::META_SOURCE_LABEL, sanitize_text_field(wp_unslash((string) ($_POST['jazireh_event_source_label'] ?? ''))));
        update_post_meta($post_id, self::META_SOURCE_URL, esc_url_raw((string) ($_POST['jazireh_event_source_url'] ?? '')));
        update_post_meta($post_id, self::META_FEATURED, isset($_POST['jazireh_event_featured']) ? '1' : '0');
    }

    public static function columns($columns)
    {
        $result = array();
        foreach ($columns as $key => $label) {
            $result[$key] = $label;
            if ($key === 'title') {
                $result['jazireh_event_type'] = 'نوع';
                $result['jazireh_event_category'] = 'دسته';
                $result['jazireh_event_start'] = 'تاریخ';
                $result['jazireh_event_status'] = 'وضعیت';
                $result['jazireh_event_featured'] = 'ویژه';
            }
        }
        return $result;
    }

    public static function column_content($column, $post_id)
    {
        if ($column === 'jazireh_event_type') {
            $type = get_post_meta($post_id, self::META_TYPE, true) ?: get_post_meta($post_id, self::META_CATEGORY, true);
            echo esc_html(self::event_types()[$type] ?? self::event_types()['other']);
        }
        if ($column === 'jazireh_event_category') {
            echo esc_html(self::category_label(get_post_meta($post_id, self::META_CATEGORY, true)));
        }
        if ($column === 'jazireh_event_start') {
            echo esc_html(get_post_meta($post_id, self::META_START_DATE, true) ?: '—');
        }
        if ($column === 'jazireh_event_status') {
            echo esc_html(self::event_status(get_post_meta($post_id, self::META_END_DATE, true) ?: get_post_meta($post_id, self::META_START_DATE, true)) === 'upcoming' ? 'پیش‌رو' : 'گذشته');
        }
        if ($column === 'jazireh_event_featured') {
            echo get_post_meta($post_id, self::META_FEATURED, true) === '1' ? '<span class="dashicons dashicons-star-filled" style="color:#dba617"></span>' : '—';
        }
    }

    public static function sortable_columns($columns)
    {
        $columns['jazireh_event_start'] = 'jazireh_event_start';
        return $columns;
    }

    public static function sort_admin_columns($query)
    {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== self::POST_TYPE) {
            return;
        }
        if ($query->get('orderby') === 'jazireh_event_start') {
            $query->set('meta_key', self::META_START_DATE);
            $query->set('orderby', 'meta_value');
        }
    }

    public static function query_events($args = array())
    {
        $page = max(1, (int) ($args['page'] ?? 1));
        $per_page = min(max((int) ($args['per_page'] ?? 10), 1), 50);
        $type = sanitize_key((string) ($args['type'] ?? ''));
        $status = sanitize_key((string) ($args['status'] ?? 'upcoming'));
        $today = self::date(current_time('timestamp', true));

        $meta_query = array('relation' => 'AND');
        if ($type && array_key_exists($type, self::event_types())) {
            $meta_query[] = array(
                'relation' => 'OR',
                array('key' => self::META_TYPE, 'value' => $type, 'compare' => '='),
                array('key' => self::META_CATEGORY, 'value' => $type, 'compare' => '='),
            );
        }
        if ($status === 'upcoming') {
            $meta_query[] = array('key' => self::META_END_DATE, 'value' => $today, 'compare' => '>=', 'type' => 'DATE');
        } elseif ($status === 'past') {
            $meta_query[] = array('key' => self::META_END_DATE, 'value' => $today, 'compare' => '<', 'type' => 'DATE');
        }

        $query_args = array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_key' => self::META_START_DATE,
            'orderby' => 'meta_value',
            'order' => $status === 'past' ? 'DESC' : 'ASC',
            'no_found_rows' => false,
        );
        if (count($meta_query) > 1) {
            $query_args['meta_query'] = $meta_query;
        }

        $query = new WP_Query($query_args);
        $items = array_map(array(__CLASS__, 'format_event'), $query->posts);
        $total = (int) $query->found_posts;
        $total_pages = max(1, (int) $query->max_num_pages);

        return array(
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $per_page,
            'totalPages' => $total_pages,
            'hasMore' => $page < $total_pages,
            'filters' => array(
                'status' => in_array($status, array('upcoming', 'past', 'all'), true) ? $status : 'upcoming',
                'type' => $type,
            ),
            'types' => self::event_types(),
        );
    }

    public static function format_event(WP_Post $post)
    {
        $types = self::event_types();
        $type = get_post_meta($post->ID, self::META_TYPE, true) ?: get_post_meta($post->ID, self::META_CATEGORY, true) ?: 'other';
        if (!array_key_exists($type, $types)) {
            $type = 'other';
        }
        $start = get_post_meta($post->ID, self::META_START_DATE, true);
        $end = get_post_meta($post->ID, self::META_END_DATE, true) ?: $start;
        $image = get_the_post_thumbnail_url($post->ID, 'large') ?: '';
        $excerpt = wp_strip_all_tags($post->post_excerpt ?: wp_trim_words($post->post_content, 32, '...'));

        return array(
            'id' => (int) $post->ID,
            'slug' => $post->post_name,
            'title' => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
            'eventType' => $type,
            'eventTypeLabel' => $types[$type],
            'startDate' => self::to_atom($start),
            'endDate' => self::to_atom($end),
            'status' => self::event_status($end ?: $start),
            'visibility' => get_post_meta($post->ID, self::META_VISIBILITY, true),
            'description' => $excerpt,
            'content' => wp_kses_post(apply_filters('the_content', $post->post_content)),
            'image' => $image,
            'url' => '/events/' . $post->post_name,
            'source' => array(
                'label' => get_post_meta($post->ID, self::META_SOURCE_LABEL, true),
                'url' => get_post_meta($post->ID, self::META_SOURCE_URL, true),
            ),
            'seo' => array(
                'title' => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
                'description' => $excerpt,
                'image' => $image,
                'schema' => self::schema($post, $type, $start, $end, $excerpt, $image),
            ),
            'publishedAt' => get_post_time(DATE_ATOM, true, $post),
        );
    }

    public static function schema(WP_Post $post, $type, $start, $end, $description, $image)
    {
        $source_url = get_post_meta($post->ID, self::META_SOURCE_URL, true);
        $visibility = get_post_meta($post->ID, self::META_VISIBILITY, true);
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
            'description' => $description,
            'startDate' => self::to_atom($start),
            'endDate' => self::to_atom($end ?: $start),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
            'location' => array('@type' => 'Place', 'name' => $visibility ?: 'آسمان'),
            'organizer' => array('@type' => 'Organization', 'name' => 'جزیره نجوم'),
            'url' => home_url('/events/' . $post->post_name . '/'),
            'additionalType' => $type,
        );
        if ($image) {
            $schema['image'] = array($image);
        }
        if ($source_url) {
            $schema['sameAs'] = esc_url_raw($source_url);
        }
        return $schema;
    }

    private static function curated_events($limit, $now, $days)
    {
        $today = self::date($now);
        $end = self::date($now + (min(max((int) $days, 7), 365) * DAY_IN_SECONDS));
        $query = new WP_Query(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => min(max((int) $limit, 1), 24),
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => self::META_START_DATE, 'value' => array($today, $end), 'compare' => 'BETWEEN', 'type' => 'DATE'),
                array('key' => self::META_END_DATE, 'value' => $today, 'compare' => '>=', 'type' => 'DATE'),
            ),
            'orderby' => 'meta_value',
            'meta_key' => self::META_START_DATE,
            'order' => 'ASC',
            'no_found_rows' => true,
        ));

        return array_map(array(__CLASS__, 'format_post'), $query->posts);
    }

    private static function generated_events($now, $days, $planet_payload = null)
    {
        $location = self::location_meta();
        $observer = self::observer($location);
        $cache_key = 'jazireh_events_generated_' . md5(self::date($now) . '|' . (int) $days . '|' . $location['lat'] . '|' . $location['lng'] . '|' . wp_timezone_string());
        if ($planet_payload === null) {
            $cached = get_transient($cache_key);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $items = array();
        $items = array_merge($items, self::moon_events($now, $days, $observer));
        $items = array_merge($items, self::seasonal_events($now, $days));
        $items = array_merge($items, self::meteor_events($now, $days));
        $items = array_merge($items, self::planet_events($now, $planet_payload, $observer));
        if ($planet_payload === null) {
            set_transient($cache_key, $items, 45 * MINUTE_IN_SECONDS);
        }
        return $items;
    }

    private static function moon_events($now, $days, $observer)
    {
        $moon_widget = Jazireh_Moon_Service::widget();
        $moon_data = is_array($moon_widget['data'] ?? null) ? $moon_widget['data'] : array();
        if (empty($moon_data)) {
            return array();
        }

        $full = self::date_from_atom($moon_data['nextFullMoon'] ?? '');
        $new = self::date_from_atom($moon_data['nextNewMoon'] ?? '');

        $events = array();
        if ($full) {
            $events[] = self::event(
                'calculated-moon-full-' . $full,
                'ماه بدر بعدی',
                'moon_phase',
                $full,
                $full,
                $full,
                'تمام شب',
                78,
                'شرق تا غرب',
                'براساس محاسبه ماه انجام‌شده در سرویس ماه، این بازه برای رصد شبانه مناسب‌تر است.',
                'moon-service',
                'estimated',
                '',
                array(
                    'accuracy' => 'estimated',
                    'observer' => $observer,
                    'displayWarning' => 'این زمان براساس خروجی سرویس ماه و نه لحظه دقیق افمریس JPL نمایش داده می‌شود.',
                    'calculatedAt' => wp_date(DATE_ATOM, $now),
                )
            );
        }
        if ($new) {
            $events[] = self::event(
                'calculated-moon-new-' . $new,
                'ماه نو بعدی',
                'moon_phase',
                $new,
                $new,
                $new,
                'پس از تاریکی کامل',
                92,
                'آسمان تاریک',
                'براساس محاسبه ماه انجام‌شده در سرویس ماه، این تاریخ برای آسمان تاریک مناسب‌تر است.',
                'moon-service',
                'estimated',
                '',
                array(
                    'accuracy' => 'estimated',
                    'observer' => $observer,
                    'displayWarning' => 'این زمان براساس خروجی سرویس ماه و نه لحظه دقیق افمریس JPL نمایش داده می‌شود.',
                    'calculatedAt' => wp_date(DATE_ATOM, $now),
                )
            );
        }

        return $events;
    }

    private static function seasonal_events($now, $days)
    {
        $year = (int) wp_date('Y', $now);
        $dates = array(
            array('vernal-equinox', 'اعتدال بهاری', 'seasonal', $year . '-03-20', 'تمام روز', 45, '', 'طول روز و شب تقریبا برابر می‌شود و آغاز فصل بهار نجومی است.'),
            array('summer-solstice', 'انقلاب تابستانی', 'seasonal', $year . '-06-21', 'تمام روز', 42, '', 'طولانی‌ترین روز سال و نقطه آغاز تابستان نجومی است.'),
            array('autumnal-equinox', 'اعتدال پاییزی', 'seasonal', $year . '-09-22', 'تمام روز', 45, '', 'طول روز و شب دوباره تقریبا برابر می‌شود و پاییز نجومی آغاز می‌شود.'),
            array('winter-solstice', 'انقلاب زمستانی', 'seasonal', $year . '-12-21', 'تمام روز', 42, '', 'بلندترین شب سال و آغاز زمستان نجومی است.'),
            array('vernal-equinox-next', 'اعتدال بهاری', 'seasonal', ($year + 1) . '-03-20', 'تمام روز', 45, '', 'طول روز و شب تقریبا برابر می‌شود و آغاز فصل بهار نجومی است.'),
        );

        return self::filter_static_events($dates, $now, $days);
    }

    private static function meteor_events($now, $days)
    {
        $year = (int) wp_date('Y', $now);
        $events = array();
        foreach (array($year, $year + 1) as $event_year) {
            $events[] = array('quadrantids-' . $event_year, 'بارش شهابی ربعی', 'meteor_shower', $event_year . '-01-03', 'پس از نیمه‌شب', 78, 'شمال شرق', 'در آسمان تاریک می‌تواند یکی از بارش‌های پرشمار سال باشد.');
            $events[] = array('eta-aquariids-' . $event_year, 'بارش شهابی اتا دلوی', 'meteor_shower', $event_year . '-05-06', 'پیش از طلوع', 70, 'شرق', 'برای رصد بهتر، افق شرقی باز و آسمان تاریک کمک زیادی می‌کند.');
            $events[] = array('perseids-' . $event_year, 'بارش شهابی برساوشی', 'meteor_shower', $event_year . '-08-12', 'پس از نیمه‌شب', 88, 'شمال شرق', 'یکی از محبوب‌ترین بارش‌های شهابی سال برای رصد عمومی است.');
            $events[] = array('geminids-' . $event_year, 'بارش شهابی جوزایی', 'meteor_shower', $event_year . '-12-14', 'نیمه‌شب تا بامداد', 90, 'شرق تا جنوب', 'در شرایط آسمان تاریک معمولا یکی از قابل‌اعتمادترین بارش‌های سال است.');
        }

        return self::filter_static_events($events, $now, $days);
    }

    private static function planet_events($now, $planet_payload = null, $observer = null)
    {
        if (!is_array($planet_payload)) {
            return array();
        }

        $payload = $planet_payload;
        $items = array();
        foreach (array_slice((array) ($payload['items'] ?? array()), 0, 2) as $planet) {
            if (!empty($planet['isFallback']) || $planet['visibilityScore'] === null || (int) $planet['visibilityScore'] < 58) {
                continue;
            }
            $items[] = self::event(
                'generated-planet-' . sanitize_key($planet['id']) . '-' . self::date($now),
                'فرصت رصد ' . $planet['name'],
                'planet_visibility',
                self::date($now),
                self::date($now),
                self::date($now),
                $planet['bestTime'],
                (int) $planet['visibilityScore'],
                $planet['direction'],
                $planet['summary'],
                $planet['source'] ?? 'jpl-horizons',
                $planet['confidence'] ?? 'high',
                '',
                array(
                    'accuracy' => $planet['accuracy'] ?? 'authoritative',
                    'observer' => is_array($planet['observer'] ?? null) ? $planet['observer'] : ($observer ?: self::observer(self::location_meta())),
                    'displayWarning' => 'دیدپذیری از ارتفاع واقعی جرم، موقعیت خورشید، روشنایی ماه و وضعیت ابرناکی در دسترس محاسبه شده است.',
                    'calculatedAt' => $planet['calculatedAt'] ?? wp_date(DATE_ATOM, $now),
                    'generatedAtUtc' => $planet['generatedAtUtc'] ?? gmdate(DATE_ATOM, $now),
                )
            );
        }
        return $items;
    }

    private static function filter_static_events($events, $now, $days)
    {
        $result = array();
        $today = self::date($now);
        $max = self::date($now + (min(max((int) $days, 7), 365) * DAY_IN_SECONDS));
        foreach ($events as $event) {
            if ($event[3] < $today || $event[3] > $max) {
                continue;
            }
            $result[] = self::event($event[0], $event[1], $event[2], $event[3], $event[3], $event[3], $event[4], $event[5], $event[6], $event[7], 'curated-static', 'medium', '', array(
                'accuracy' => 'curated-approximate',
                'displayWarning' => 'رویداد تقویمی/تحریریه‌ای است و زمان آن باید برای نسخه نهایی با منبع رسمی بررسی شود.',
            ));
        }
        return $result;
    }

    private static function format_post(WP_Post $post)
    {
        $start = self::valid_date(get_post_meta($post->ID, self::META_START_DATE, true)) ?: get_post_time('Y-m-d', true, $post);
        $peak = self::valid_datetime(get_post_meta($post->ID, self::META_PEAK_DATE, true)) ?: $start;
        $end = self::valid_date(get_post_meta($post->ID, self::META_END_DATE, true)) ?: $start;
        if (substr($end, 0, 10) < substr($start, 0, 10)) {
            $end = $start;
        }
        $score = min(max(absint(get_post_meta($post->ID, self::META_VISIBILITY_SCORE, true) ?: 70), 0), 100);
        $summary = $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($post->post_content), 28, '...');

        return self::event(
            'curated-' . (int) $post->ID,
            html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
            get_post_meta($post->ID, self::META_CATEGORY, true) ?: 'observing_tip',
            $start,
            $peak,
            $end,
            get_post_meta($post->ID, self::META_BEST_TIME, true) ?: 'زمان اعلام‌شده',
            $score,
            get_post_meta($post->ID, self::META_DIRECTION, true) ?: '',
            $summary,
            get_post_meta($post->ID, self::META_FEATURED, true) === '1' ? 'wordpress-featured' : 'wordpress',
            'curated',
            get_post_meta($post->ID, self::META_SOURCE_URL, true),
            array(
                'accuracy' => 'curated',
                'observer' => self::observer(self::location_meta()),
                'displayWarning' => get_post_meta($post->ID, self::META_SOURCE_URL, true) ? '' : 'رویداد دستی وردپرس است؛ منبع معتبر باید در پنل مدیریت ثبت شود.',
            )
        );
    }

    private static function event($id, $title, $category, $start, $peak, $end, $best_time, $score, $direction, $summary, $source, $confidence, $source_url = '', $meta = array())
    {
        $score = min(max((int) $score, 0), 100);
        $observer = is_array($meta['observer'] ?? null) ? $meta['observer'] : self::observer(self::location_meta());
        $is_fallback = !empty($meta['isFallback']);
        $accuracy = sanitize_key((string) ($meta['accuracy'] ?? ($confidence === 'curated' ? 'curated' : 'estimated')));
        $display_warning = array_key_exists('displayWarning', $meta) ? (string) $meta['displayWarning'] : ($confidence === 'curated' ? '' : 'نمایش تقریبی');
        return array(
            'id' => sanitize_key($id),
            'title' => sanitize_text_field($title),
            'category' => sanitize_key($category),
            'categoryLabel' => self::category_label($category),
            'startDate' => sanitize_text_field($start),
            'peakDate' => sanitize_text_field($peak),
            'endDate' => sanitize_text_field($end),
            'bestTime' => sanitize_text_field($best_time),
            'visibilityScore' => $score,
            'visibilityLabel' => self::visibility_label($score),
            'direction' => sanitize_text_field($direction),
            'summary' => wp_strip_all_tags($summary),
            'source' => sanitize_key($source),
            'accuracy' => $accuracy,
            'confidence' => sanitize_key($confidence),
            'calculatedAt' => sanitize_text_field((string) ($meta['calculatedAt'] ?? wp_date(DATE_ATOM))),
            'generatedAtUtc' => sanitize_text_field((string) ($meta['generatedAtUtc'] ?? gmdate(DATE_ATOM))),
            'observer' => $observer,
            'timezone' => wp_timezone_string(),
            'isFallback' => $is_fallback,
            'fallbackReason' => sanitize_text_field((string) ($meta['fallbackReason'] ?? '')),
            'displayWarning' => sanitize_text_field($display_warning),
            'warning' => sanitize_text_field($display_warning),
            'sourceUrl' => esc_url_raw($source_url),
        );
    }

    private static function moon_phase_samples($now, $days, $observer)
    {
        $samples = array();
        $start = strtotime(self::date($now) . ' 12:00:00 UTC');
        for ($day = 0; $day <= (int) $days; $day++) {
            $timestamp = $start + ($day * DAY_IN_SECONDS);
            $ephemeris = Jazireh_Ephemeris::object('moon', $observer, $timestamp, 'event-moon-phase');
            $fallback = is_array($ephemeris['fallback'] ?? null) ? $ephemeris['fallback'] : array('isFallback' => true);
            $appearance = is_array($ephemeris['appearance'] ?? null) ? $ephemeris['appearance'] : array();
            if (!empty($fallback['isFallback']) || !is_numeric($appearance['phaseAngle'] ?? null) || !is_numeric($appearance['illumination'] ?? null)) {
                continue;
            }
            $samples[] = array(
                'date' => self::date($timestamp),
                'timestamp' => $timestamp,
                'phaseAngle' => (float) $appearance['phaseAngle'],
                'illumination' => (float) $appearance['illumination'],
            );
        }
        return $samples;
    }

    private static function date_from_atom($value)
    {
        $timestamp = strtotime((string) $value);
        return $timestamp ? self::date($timestamp) : '';
    }

    private static function first_moon_phase_extreme($samples, $type)
    {
        $count = count($samples);
        for ($index = 1; $index < $count - 1; $index++) {
            $previous = $samples[$index - 1];
            $current = $samples[$index];
            $next = $samples[$index + 1];
            if ($type === 'full' && $current['phaseAngle'] <= $previous['phaseAngle'] && $current['phaseAngle'] <= $next['phaseAngle'] && $current['illumination'] >= 95) {
                return $current;
            }
            if ($type === 'new' && $current['phaseAngle'] >= $previous['phaseAngle'] && $current['phaseAngle'] >= $next['phaseAngle'] && $current['illumination'] <= 5) {
                return $current;
            }
        }

        return null;
    }

    private static function field($post_id, $meta_key, $label, $type = 'text', $placeholder = '')
    {
        $name = str_replace('_jazireh_event_', 'jazireh_event_', $meta_key);
        $value = get_post_meta($post_id, $meta_key, true);
        ?>
        <p><label for="<?php echo esc_attr($name); ?>"><strong><?php echo esc_html($label); ?></strong></label>
            <input type="<?php echo esc_attr($type); ?>" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" class="widefat" placeholder="<?php echo esc_attr($placeholder); ?>">
        </p>
        <?php
    }

    private static function moon_age($timestamp)
    {
        $jd = ($timestamp / DAY_IN_SECONDS) + 2440587.5;
        $age = fmod($jd - self::KNOWN_NEW_MOON_JD, self::SYNODIC_MONTH);
        return $age < 0 ? $age + self::SYNODIC_MONTH : $age;
    }

    private static function valid_date($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $date = DateTime::createFromFormat('!Y-m-d', $value, wp_timezone());
        $errors = DateTime::getLastErrors();
        if (!$date || (is_array($errors) && ($errors['warning_count'] || $errors['error_count']))) {
            return '';
        }
        return $date->format('Y-m-d');
    }

    private static function valid_datetime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $date_only = self::valid_date($value);
        if ($date_only !== '') {
            return $date_only;
        }
        $date = DateTime::createFromFormat('!Y-m-d\TH:i', $value, wp_timezone());
        $errors = DateTime::getLastErrors();
        if (!$date || (is_array($errors) && ($errors['warning_count'] || $errors['error_count']))) {
            return '';
        }
        return $date->format('Y-m-d\TH:i');
    }

    private static function to_atom($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        try {
            $date = new DateTime(str_replace('T', ' ', $value), wp_timezone());
            return $date->format(DATE_ATOM);
        } catch (Exception $e) {
            return '';
        }
    }

    private static function event_status($end)
    {
        $timestamp = strtotime((string) $end);
        if (!$timestamp) {
            return 'upcoming';
        }
        return $timestamp >= current_time('timestamp') ? 'upcoming' : 'past';
    }

    private static function date($timestamp)
    {
        return wp_date('Y-m-d', $timestamp);
    }

    private static function location_meta()
    {
        $settings = get_option(Jazireh_Settings::OPTION_NAME, array());
        $city = is_array($settings) && !empty($settings['default_city']) ? (string) $settings['default_city'] : 'تهران';
        return array(
            'city' => $city === 'تهران' ? 'تهران، ایران' : $city,
            'lat' => is_array($settings) && isset($settings['default_latitude']) ? (float) $settings['default_latitude'] : 35.6892,
            'lng' => is_array($settings) && isset($settings['default_longitude']) ? (float) $settings['default_longitude'] : 51.3890,
        );
    }

    private static function observer($location)
    {
        return array(
            'city' => $location['city'] ?? 'تهران، ایران',
            'latitude' => isset($location['lat']) ? (float) $location['lat'] : 35.6892,
            'longitude' => isset($location['lng']) ? (float) $location['lng'] : 51.3890,
            'timezone' => wp_timezone_string(),
        );
    }

    private static function moon_interference_label($illumination)
    {
        if ($illumination >= 75) {
            return 'زیاد';
        }
        if ($illumination >= 45) {
            return 'متوسط';
        }
        return 'کم';
    }

    private static function visibility_label($score)
    {
        if ($score >= 82) {
            return 'عالی';
        }
        if ($score >= 64) {
            return 'خوب';
        }
        if ($score >= 45) {
            return 'متوسط';
        }
        return 'محدود';
    }

    private static function categories()
    {
        return array(
            'meteor_shower' => 'بارش شهابی',
            'moon_phase' => 'فاز ماه',
            'lunar_eclipse' => 'ماه‌گرفتگی',
            'solar_eclipse' => 'خورشیدگرفتگی',
            'planet_conjunction' => 'هم‌نشینی سیاره‌ای',
            'planet_opposition' => 'مقابله سیاره‌ای',
            'planet_visibility' => 'دیدپذیری سیاره',
            'seasonal' => 'رویداد فصلی',
            'space_event' => 'رویداد فضایی',
            'observing_tip' => 'فرصت رصد',
            'local_event' => 'رویداد محلی',
        );
    }

    private static function category_label($category)
    {
        $categories = self::categories();
        return $categories[$category] ?? 'رویداد نجومی';
    }

    private static function sort_events($left, $right)
    {
        $date_compare = strcmp((string) $left['startDate'], (string) $right['startDate']);
        if ($date_compare !== 0) {
            return $date_compare;
        }
        return (int) $right['visibilityScore'] <=> (int) $left['visibilityScore'];
    }

    private static function payload_cache_key($limit, $days, $timestamp, $planet_payload = null)
    {
        $bucket = (int) floor(((int) $timestamp) / (30 * MINUTE_IN_SECONDS)) * (30 * MINUTE_IN_SECONDS);
        $planet_signature = '';
        if (is_array($planet_payload) && !empty($planet_payload['items']) && is_array($planet_payload['items'])) {
            $planet_signature = md5(wp_json_encode(array_map(static function ($planet) {
                return array(
                    'id' => $planet['id'] ?? '',
                    'score' => $planet['visibilityScore'] ?? null,
                    'status' => $planet['status'] ?? '',
                    'time' => $planet['calculatedAt'] ?? '',
                );
            }, $planet_payload['items'])));
        }

        return 'jazireh_events_payload_' . md5(wp_json_encode(array(
            'limit' => min(max((int) $limit, 1), 24),
            'days' => min(max((int) $days, 7), 365),
            'bucket' => $bucket,
            'timezone' => wp_timezone_string(),
            'location' => self::location_meta(),
            'planetSignature' => $planet_signature,
        )));
    }

    private static function payload_cache_ttl($payload)
    {
        return !empty($payload['isFallback']) ? 10 * MINUTE_IN_SECONDS : 30 * MINUTE_IN_SECONDS;
    }
}
