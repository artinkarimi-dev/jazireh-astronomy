<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Objects
{
    const POST_TYPE = 'jazireh_object';
    const META_LEGACY_ID = '_jazireh_object_legacy_id';
    const META_NAME_EN = '_jazireh_object_name_en';
    const META_TYPE = '_jazireh_object_type';
    const META_COLOR = '_jazireh_object_color';
    const META_SIZE = '_jazireh_object_size';
    const META_DISTANCE = '_jazireh_object_distance';
    const META_SPEED = '_jazireh_object_speed';
    const META_RING = '_jazireh_object_ring';
    const META_FACTS = '_jazireh_object_facts';
    const META_STATS = '_jazireh_object_stats';
    const META_SORT_ORDER = '_jazireh_object_sort_order';

    public static function boot()
    {
        add_action('init', array(__CLASS__, 'register_content_type'));
        add_action('init', array(__CLASS__, 'maybe_cleanup_duplicates'), 25);
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
                'name' => 'اجرام آسمانی',
                'singular_name' => 'جرم آسمانی',
                'menu_name' => 'اجرام آسمانی',
                'add_new' => 'افزودن جرم',
                'add_new_item' => 'افزودن جرم جدید',
                'edit_item' => 'ویرایش جرم',
                'new_item' => 'جرم جدید',
                'view_item' => 'مشاهده جرم',
                'search_items' => 'جستجو در اجرام',
                'not_found' => 'جرمی پیدا نشد',
                'all_items' => 'همه اجرام'
            ),
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-superhero',
            'menu_position' => 6,
            'supports' => array('title', 'revisions'),
            'has_archive' => false,
            'rewrite' => false,
            'show_in_nav_menus' => false,
            'map_meta_cap' => true,
            'capability_type' => 'post'
        ));
    }

    public static function add_meta_boxes()
    {
        add_meta_box('jazireh_object_details', 'تنظیمات نمایش جرم', array(__CLASS__, 'render_meta_box'), self::POST_TYPE, 'normal', 'high');
    }

    public static function render_meta_box($post)
    {
        wp_nonce_field('jazireh_object_save', 'jazireh_object_nonce');
        $fields = self::meta_fields($post->ID);
        ?>
        <div class="jazireh-object-grid">
            <p>
                <label for="jazireh_object_name_en"><strong>نام انگلیسی</strong></label>
                <input type="text" id="jazireh_object_name_en" name="jazireh_object_name_en" value="<?php echo esc_attr($fields['nameEn']); ?>" class="widefat">
            </p>
            <p>
                <label for="jazireh_object_type"><strong>نوع</strong></label>
                <input type="text" id="jazireh_object_type" name="jazireh_object_type" value="<?php echo esc_attr($fields['type']); ?>" class="widefat">
            </p>
            <p>
                <label for="jazireh_object_color"><strong>رنگ</strong></label>
                <input type="text" id="jazireh_object_color" name="jazireh_object_color" value="<?php echo esc_attr($fields['color']); ?>" class="widefat" placeholder="#8f8a80">
            </p>
            <p>
                <label for="jazireh_object_size"><strong>اندازه نمایشی</strong></label>
                <input type="number" step="0.01" id="jazireh_object_size" name="jazireh_object_size" value="<?php echo esc_attr($fields['size']); ?>" class="widefat">
            </p>
            <p>
                <label for="jazireh_object_distance"><strong>فاصله مداری</strong></label>
                <input type="number" step="0.01" id="jazireh_object_distance" name="jazireh_object_distance" value="<?php echo esc_attr($fields['distance']); ?>" class="widefat">
            </p>
            <p>
                <label for="jazireh_object_speed"><strong>سرعت مداری</strong></label>
                <input type="number" step="0.00001" id="jazireh_object_speed" name="jazireh_object_speed" value="<?php echo esc_attr($fields['speed']); ?>" class="widefat">
            </p>
            <p>
                <label for="jazireh_object_sort_order"><strong>ترتیب نمایش</strong></label>
                <input type="number" step="1" id="jazireh_object_sort_order" name="jazireh_object_sort_order" value="<?php echo esc_attr($fields['sortOrder']); ?>" class="widefat">
            </p>
            <p>
                <label>
                    <input type="checkbox" name="jazireh_object_ring" value="1" <?php checked($fields['ring'], '1'); ?>>
                    دارای حلقه
                </label>
            </p>
        </div>
        <p>
            <label for="jazireh_object_facts"><strong>واقعیت‌ها</strong></label>
            <textarea id="jazireh_object_facts" name="jazireh_object_facts" rows="6" class="widefat"><?php echo esc_textarea($fields['facts']); ?></textarea>
            <small>هر واقعیت را در یک خط جدا وارد کنید.</small>
        </p>
        <p>
            <label for="jazireh_object_stats"><strong>آمارها</strong></label>
            <textarea id="jazireh_object_stats" name="jazireh_object_stats" rows="6" class="widefat"><?php echo esc_textarea($fields['stats']); ?></textarea>
            <small>هر خط به شکل <code>key:value</code>، مثلا <code>diameter:۱۲٬۷۴۲ کیلومتر</code></small>
        </p>
        <style>
            .jazireh-object-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
            @media (max-width:782px){.jazireh-object-grid{grid-template-columns:1fr}}
        </style>
        <?php
    }

    public static function save_meta($post_id)
    {
        if (!isset($_POST['jazireh_object_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jazireh_object_nonce'])), 'jazireh_object_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $map = array(
            'jazireh_object_name_en' => self::META_NAME_EN,
            'jazireh_object_type' => self::META_TYPE,
            'jazireh_object_color' => self::META_COLOR,
        );
        foreach ($map as $field => $meta_key) {
            $value = isset($_POST[$field]) ? sanitize_text_field(wp_unslash($_POST[$field])) : '';
            update_post_meta($post_id, $meta_key, $value);
        }

        update_post_meta($post_id, self::META_SIZE, self::sanitize_decimal($_POST['jazireh_object_size'] ?? '0', 2));
        update_post_meta($post_id, self::META_DISTANCE, self::sanitize_decimal($_POST['jazireh_object_distance'] ?? '0', 2));
        update_post_meta($post_id, self::META_SPEED, self::sanitize_decimal($_POST['jazireh_object_speed'] ?? '0', 5));
        update_post_meta($post_id, self::META_SORT_ORDER, absint($_POST['jazireh_object_sort_order'] ?? 0));
        update_post_meta($post_id, self::META_RING, isset($_POST['jazireh_object_ring']) ? '1' : '0');
        update_post_meta($post_id, self::META_FACTS, self::sanitize_facts($_POST['jazireh_object_facts'] ?? ''));
        update_post_meta($post_id, self::META_STATS, self::sanitize_stats($_POST['jazireh_object_stats'] ?? ''));
    }

    public static function columns($columns)
    {
        $result = array();
        foreach ($columns as $key => $label) {
            $result[$key] = $label;
            if ($key === 'title') {
                $result['jazireh_object_type'] = 'نوع';
                $result['jazireh_object_sort_order'] = 'ترتیب';
            }
        }
        return $result;
    }

    public static function column_content($column, $post_id)
    {
        if ($column === 'jazireh_object_type') {
            echo esc_html(get_post_meta($post_id, self::META_TYPE, true));
        }
        if ($column === 'jazireh_object_sort_order') {
            echo esc_html((string) get_post_meta($post_id, self::META_SORT_ORDER, true));
        }
    }

    public static function sortable_columns($columns)
    {
        $columns['jazireh_object_sort_order'] = 'jazireh_object_sort_order';
        return $columns;
    }

    public static function sort_admin_columns($query)
    {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== self::POST_TYPE) {
            return;
        }
        if ($query->get('orderby') === 'jazireh_object_sort_order') {
            $query->set('meta_key', self::META_SORT_ORDER);
            $query->set('orderby', 'meta_value_num');
        }
    }

    public static function maybe_cleanup_duplicates()
    {
        $query = new WP_Query(array(
            'post_type' => self::POST_TYPE,
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'meta_key' => self::META_LEGACY_ID,
        ));

        $grouped = array();
        foreach ($query->posts as $post) {
            $legacy_id = (string) get_post_meta($post->ID, self::META_LEGACY_ID, true);
            if ($legacy_id === '') {
                continue;
            }
            if (!isset($grouped[$legacy_id])) {
                $grouped[$legacy_id] = array();
            }
            $grouped[$legacy_id][] = $post;
        }

        $trashed = 0;
        foreach ($grouped as $posts) {
            if (count($posts) < 2) {
                continue;
            }

            usort($posts, array(__CLASS__, 'compare_duplicate_candidates'));
            $keep = array_shift($posts);
            foreach ($posts as $duplicate) {
                if ((int) $duplicate->ID === (int) $keep->ID) {
                    continue;
                }
                if (wp_trash_post($duplicate->ID)) {
                    $trashed++;
                }
            }
        }

        if ($trashed > 0) {
            update_option('jazireh_objects_duplicate_cleanup', array(
                'trashed_count' => $trashed,
                'updated_at' => current_time('mysql', 1),
            ), false);
        }
    }

    public static function list_objects($args = array())
    {
        $query = new WP_Query(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => isset($args['limit']) ? (int) $args['limit'] : -1,
            'orderby' => 'meta_value_num',
            'meta_key' => self::META_SORT_ORDER,
            'order' => 'ASC',
            'no_found_rows' => true,
        ));

        return array_map(array(__CLASS__, 'format_object'), $query->posts);
    }

    public static function format_object(WP_Post $post)
    {
        return array(
            'id' => (int) (get_post_meta($post->ID, self::META_LEGACY_ID, true) ?: $post->ID),
            'slug' => $post->post_name,
            'name' => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
            'nameEn' => (string) get_post_meta($post->ID, self::META_NAME_EN, true),
            'type' => (string) get_post_meta($post->ID, self::META_TYPE, true),
            'color' => (string) get_post_meta($post->ID, self::META_COLOR, true),
            'size' => (float) get_post_meta($post->ID, self::META_SIZE, true),
            'distance' => (float) get_post_meta($post->ID, self::META_DISTANCE, true),
            'speed' => (float) get_post_meta($post->ID, self::META_SPEED, true),
            'ring' => get_post_meta($post->ID, self::META_RING, true) === '1',
            'facts' => self::facts_to_array(get_post_meta($post->ID, self::META_FACTS, true)),
            'stats' => self::stats_to_array(get_post_meta($post->ID, self::META_STATS, true)),
        );
    }

    private static function meta_fields($post_id)
    {
        return array(
            'nameEn' => (string) get_post_meta($post_id, self::META_NAME_EN, true),
            'type' => (string) get_post_meta($post_id, self::META_TYPE, true),
            'color' => (string) get_post_meta($post_id, self::META_COLOR, true),
            'size' => (string) get_post_meta($post_id, self::META_SIZE, true),
            'distance' => (string) get_post_meta($post_id, self::META_DISTANCE, true),
            'speed' => (string) get_post_meta($post_id, self::META_SPEED, true),
            'ring' => (string) get_post_meta($post_id, self::META_RING, true),
            'sortOrder' => (string) get_post_meta($post_id, self::META_SORT_ORDER, true),
            'facts' => (string) get_post_meta($post_id, self::META_FACTS, true),
            'stats' => (string) get_post_meta($post_id, self::META_STATS, true),
        );
    }

    private static function existing_post_id($legacy_id, $slug)
    {
        $query = new WP_Query(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'name' => sanitize_title($slug),
            'meta_query' => array(
                array(
                    'key' => self::META_LEGACY_ID,
                    'value' => (string) $legacy_id,
                ),
            ),
        ));

        if (!empty($query->posts[0])) {
            return (int) $query->posts[0];
        }

        $by_slug = get_page_by_path($slug, OBJECT, self::POST_TYPE);
        return $by_slug ? (int) $by_slug->ID : 0;
    }

    private static function sanitize_decimal($value, $precision)
    {
        return number_format((float) $value, $precision, '.', '');
    }

    private static function sanitize_facts($value)
    {
        $lines = preg_split('/\r\n|\r|\n/', wp_unslash((string) $value));
        $lines = array_filter(array_map('sanitize_text_field', $lines));
        return implode("\n", $lines);
    }

    private static function sanitize_stats($value)
    {
        $lines = preg_split('/\r\n|\r|\n/', wp_unslash((string) $value));
        $clean = array();
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || strpos($line, ':') === false) {
                continue;
            }
            list($key, $stat_value) = array_map('trim', explode(':', $line, 2));
            $key = sanitize_key($key);
            $stat_value = sanitize_text_field($stat_value);
            if ($key !== '' && $stat_value !== '') {
                $clean[] = $key . ':' . $stat_value;
            }
        }
        return implode("\n", $clean);
    }

    private static function facts_from_json($value)
    {
        $facts = json_decode((string) $value, true);
        if (!is_array($facts)) {
            return '';
        }
        return implode("\n", array_map('sanitize_text_field', $facts));
    }

    private static function stats_from_json($value)
    {
        $stats = json_decode((string) $value, true);
        if (!is_array($stats)) {
            return '';
        }
        $lines = array();
        foreach ($stats as $key => $stat_value) {
            $lines[] = sanitize_key($key) . ':' . sanitize_text_field((string) $stat_value);
        }
        return implode("\n", $lines);
    }

    private static function facts_to_array($value)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value);
        return array_values(array_filter(array_map('sanitize_text_field', $lines)));
    }

    private static function stats_to_array($value)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value);
        $stats = array();
        foreach ($lines as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            list($key, $stat_value) = array_map('trim', explode(':', $line, 2));
            $key = sanitize_key($key);
            $stat_value = sanitize_text_field($stat_value);
            if ($key !== '' && $stat_value !== '') {
                $stats[$key] = $stat_value;
            }
        }
        return $stats;
    }

    private static function compare_duplicate_candidates($left, $right)
    {
        $left_slug = (string) $left->post_name;
        $right_slug = (string) $right->post_name;
        $left_plain = preg_match('/-\d+$/', $left_slug) ? 1 : 0;
        $right_plain = preg_match('/-\d+$/', $right_slug) ? 1 : 0;
        if ($left_plain !== $right_plain) {
            return $left_plain - $right_plain;
        }
        return (int) $left->ID - (int) $right->ID;
    }
}
