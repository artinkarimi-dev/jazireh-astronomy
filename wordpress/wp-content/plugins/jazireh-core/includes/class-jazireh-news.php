<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_News
{
    const POST_TYPE = 'jazireh_news';
    const TAXONOMY = 'jazireh_news_category';
    const META_READING_TIME = '_jazireh_reading_time';
    const META_FEATURED = '_jazireh_featured';
    const META_SOURCE_NAME = '_jazireh_source_name';
    const META_SOURCE_URL = '_jazireh_source_url';
    const META_TRANSLATOR = '_jazireh_translator';
    const META_IMAGE_CREDIT = '_jazireh_image_credit';

    public static function boot()
    {
        add_action('init', array(__CLASS__, 'register_content_types'));
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'save_meta'));
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array(__CLASS__, 'columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array(__CLASS__, 'column_content'), 10, 2);
        add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', array(__CLASS__, 'sortable_columns'));
        add_action('pre_get_posts', array(__CLASS__, 'sort_admin_columns'));
    }

    public static function register_content_types()
    {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => 'اخبار علمی',
                'singular_name' => 'خبر علمی',
                'menu_name' => 'اخبار علمی',
                'add_new' => 'افزودن خبر',
                'add_new_item' => 'افزودن خبر جدید',
                'edit_item' => 'ویرایش خبر',
                'new_item' => 'خبر جدید',
                'view_item' => 'مشاهده خبر',
                'search_items' => 'جستجو در اخبار',
                'not_found' => 'خبری پیدا نشد',
                'all_items' => 'همه اخبار'
            ),
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-megaphone',
            'menu_position' => 5,
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'),
            'has_archive' => false,
            'rewrite' => false,
            'show_in_nav_menus' => false,
            'map_meta_cap' => true,
            'capability_type' => 'post'
        ));

        register_taxonomy(self::TAXONOMY, self::POST_TYPE, array(
            'labels' => array(
                'name' => 'دسته‌بندی اخبار',
                'singular_name' => 'دسته‌بندی خبر',
                'search_items' => 'جستجوی دسته‌بندی',
                'all_items' => 'همه دسته‌بندی‌ها',
                'edit_item' => 'ویرایش دسته‌بندی',
                'update_item' => 'به‌روزرسانی دسته‌بندی',
                'add_new_item' => 'افزودن دسته‌بندی',
                'new_item_name' => 'نام دسته‌بندی جدید',
                'menu_name' => 'دسته‌بندی‌ها'
            ),
            'public' => true,
            'show_in_rest' => true,
            'hierarchical' => true,
            'rewrite' => false,
            'show_admin_column' => true
        ));
    }

    public static function add_meta_boxes()
    {
        add_meta_box('jazireh_news_details', 'تنظیمات نمایش خبر', array(__CLASS__, 'render_meta_box'), self::POST_TYPE, 'side', 'high');
    }

    public static function render_meta_box($post)
    {
        wp_nonce_field('jazireh_news_save', 'jazireh_news_nonce');
        $reading_time = get_post_meta($post->ID, self::META_READING_TIME, true);
        $featured = get_post_meta($post->ID, self::META_FEATURED, true);
        $source_name = get_post_meta($post->ID, self::META_SOURCE_NAME, true);
        $source_url = get_post_meta($post->ID, self::META_SOURCE_URL, true);
        $translator = get_post_meta($post->ID, self::META_TRANSLATOR, true);
        $image_credit = get_post_meta($post->ID, self::META_IMAGE_CREDIT, true);
        if ($reading_time === '') {
            $reading_time = '۵ دقیقه';
        }
        ?>
        <p>
            <label for="jazireh_reading_time"><strong>زمان مطالعه</strong></label>
            <input type="text" id="jazireh_reading_time" name="jazireh_reading_time" value="<?php echo esc_attr($reading_time); ?>" class="widefat" placeholder="مثلاً ۵ دقیقه">
        </p>
        <p>
            <label>
                <input type="checkbox" name="jazireh_featured" value="1" <?php checked($featured, '1'); ?>>
                نمایش به‌عنوان خبر ویژه
            </label>
        </p>
        <hr>
        <p>
            <label for="jazireh_source_name"><strong>نام منبع</strong></label>
            <input type="text" id="jazireh_source_name" name="jazireh_source_name" value="<?php echo esc_attr($source_name); ?>" class="widefat" placeholder="مثلاً NASA یا ESA">
        </p>
        <p>
            <label for="jazireh_source_url"><strong>لینک منبع</strong></label>
            <input type="url" id="jazireh_source_url" name="jazireh_source_url" value="<?php echo esc_url($source_url); ?>" class="widefat" placeholder="https://example.com">
        </p>
        <p>
            <label for="jazireh_translator"><strong>مترجم / تنظیم‌کننده</strong></label>
            <input type="text" id="jazireh_translator" name="jazireh_translator" value="<?php echo esc_attr($translator); ?>" class="widefat" placeholder="نام شخص یا تیم">
        </p>
        <p>
            <label for="jazireh_image_credit"><strong>اعتبار تصویر</strong></label>
            <textarea id="jazireh_image_credit" name="jazireh_image_credit" class="widefat" rows="3" placeholder="مثلاً Image: NASA / ESA / ..."><?php echo esc_textarea($image_credit); ?></textarea>
        </p>
        <p style="color:#646970;line-height:1.8">عنوان، خلاصه، متن کامل و تصویر شاخص را از بخش‌های اصلی همین صفحه وارد کنید.</p>
        <?php
    }

    public static function save_meta($post_id)
    {
        if (!isset($_POST['jazireh_news_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jazireh_news_nonce'])), 'jazireh_news_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $reading_time = isset($_POST['jazireh_reading_time']) ? sanitize_text_field(wp_unslash($_POST['jazireh_reading_time'])) : '۵ دقیقه';
        update_post_meta($post_id, self::META_READING_TIME, $reading_time ?: '۵ دقیقه');
        update_post_meta($post_id, self::META_FEATURED, isset($_POST['jazireh_featured']) ? '1' : '0');

        $source_name = isset($_POST['jazireh_source_name']) ? sanitize_text_field(wp_unslash($_POST['jazireh_source_name'])) : '';
        $source_url = isset($_POST['jazireh_source_url']) ? esc_url_raw(wp_unslash($_POST['jazireh_source_url'])) : '';
        $translator = isset($_POST['jazireh_translator']) ? sanitize_text_field(wp_unslash($_POST['jazireh_translator'])) : '';
        $image_credit = isset($_POST['jazireh_image_credit']) ? sanitize_textarea_field(wp_unslash($_POST['jazireh_image_credit'])) : '';
        update_post_meta($post_id, self::META_SOURCE_NAME, $source_name);
        update_post_meta($post_id, self::META_SOURCE_URL, $source_url);
        update_post_meta($post_id, self::META_TRANSLATOR, $translator);
        update_post_meta($post_id, self::META_IMAGE_CREDIT, $image_credit);
    }

    public static function columns($columns)
    {
        $result = array();
        foreach ($columns as $key => $label) {
            $result[$key] = $label;
            if ($key === 'title') {
                $result['jazireh_featured'] = 'ویژه';
                $result['jazireh_reading_time'] = 'زمان مطالعه';
                $result['jazireh_source'] = 'منبع';
            }
        }
        return $result;
    }

    public static function column_content($column, $post_id)
    {
        if ($column === 'jazireh_featured') {
            echo get_post_meta($post_id, self::META_FEATURED, true) === '1' ? '<span class="dashicons dashicons-star-filled" style="color:#dba617"></span>' : '—';
        }
        if ($column === 'jazireh_reading_time') {
            echo esc_html(get_post_meta($post_id, self::META_READING_TIME, true) ?: '۵ دقیقه');
        }
        if ($column === 'jazireh_source') {
            echo esc_html(get_post_meta($post_id, self::META_SOURCE_NAME, true) ?: '—');
        }
    }

    public static function sortable_columns($columns)
    {
        $columns['jazireh_featured'] = 'jazireh_featured';
        return $columns;
    }

    public static function sort_admin_columns($query)
    {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== self::POST_TYPE) {
            return;
        }
        if ($query->get('orderby') === 'jazireh_featured') {
            $query->set('meta_key', self::META_FEATURED);
            $query->set('orderby', 'meta_value_num');
        }
    }

    public static function seed_content()
    {
        if (get_option('jazireh_news_seeded')) {
            return;
        }

        $categories = array(
            'کیهان‌شناسی' => 'cosmology',
            'فراخورشیدی' => 'exoplanets',
            'ماموریت‌ها' => 'missions',
            'منظومه شمسی' => 'solar-system'
        );
        $term_ids = array();
        foreach ($categories as $name => $slug) {
            $term = term_exists($slug, self::TAXONOMY);
            if (!$term) {
                $term = wp_insert_term($name, self::TAXONOMY, array('slug' => $slug));
            }
            if (!is_wp_error($term)) {
                $term_ids[$name] = (int) (is_array($term) ? $term['term_id'] : $term);
            }
        }

        $items = array(
            array('webb-distant-galaxy', 'تلسکوپ جیمز وب ساختار یک کهکشان دوردست را با جزئیات تازه ثبت کرد', 'تصاویر تازه، نواحی زایش ستاره‌ای و توزیع غبار را با وضوح بالا نشان می‌دهند.', 'تلسکوپ فضایی جیمز وب با ابزارهای فروسرخ خود ساختارهای ظریفی از غبار و مناطق فعال ستاره‌زایی را ثبت کرده است. این داده‌ها به پژوهشگران کمک می‌کند روند رشد کهکشان‌ها در دوره‌های اولیه کیهان را بهتر بررسی کنند.', 'کیهان‌شناسی', '۵ دقیقه', '1'),
            array('new-exoplanet', 'سیاره‌ای فراخورشیدی در محدوده قابل سکونت یک ستاره آرام شناسایی شد', 'نامزد تازه جرمی سنگی است و برای بررسی جو احتمالی آن به رصدهای دقیق‌تر نیاز خواهد بود.', 'قرار گرفتن در محدوده قابل سکونت به معنی وجود قطعی آب یا حیات نیست؛ بلکه نشان می‌دهد دمای تعادلی در شرایط مناسب می‌تواند اجازه حضور آب مایع را بدهد.', 'فراخورشیدی', '۴ دقیقه', '1'),
            array('lunar-navigation-test', 'آزمایش سامانه ناوبری ماه‌نشین نسل جدید با موفقیت انجام شد', 'سامانه تازه در مرحله فرود نقشه سطح را با داده‌های دوربین تطبیق می‌دهد.', 'ناوبری مبتنی بر تطبیق عوارض سطحی یکی از فناوری‌های کلیدی برای فرود دقیق روی ماه است و به فضاپیما اجازه می‌دهد دهانه‌ها و الگوهای سطحی را شناسایی کند.', 'ماموریت‌ها', '۳ دقیقه', '0'),
            array('jupiter-cloud-belts', 'داده‌های تازه از تغییرات کمربندهای ابری مشتری منتشر شد', 'رصدهای چندطول‌موجی تغییراتی را در سرعت باد و ساختار طوفان‌ها نشان می‌دهند.', 'جو مشتری سامانه‌ای پویا از نوارهای روشن و تیره، گردابه‌ها و جریان‌های سریع است. مقایسه تصاویر مرئی و فروسرخ به دانشمندان کمک می‌کند ارتفاع ابرها و حرکت توده‌های جوی را بهتر تخمین بزنند.', 'منظومه شمسی', '۶ دقیقه', '0')
        );

        foreach ($items as $index => $item) {
            if (get_page_by_path($item[0], OBJECT, self::POST_TYPE)) {
                continue;
            }
            $post_id = wp_insert_post(array(
                'post_type' => self::POST_TYPE,
                'post_status' => 'publish',
                'post_name' => $item[0],
                'post_title' => $item[1],
                'post_excerpt' => $item[2],
                'post_content' => $item[3],
                'post_date' => gmdate('Y-m-d H:i:s', time() - ($index * DAY_IN_SECONDS))
            ));
            if (!is_wp_error($post_id)) {
                if (isset($term_ids[$item[4]])) {
                    wp_set_object_terms($post_id, array($term_ids[$item[4]]), self::TAXONOMY);
                }
                update_post_meta($post_id, self::META_READING_TIME, $item[5]);
                update_post_meta($post_id, self::META_FEATURED, $item[6]);
            }
        }
        update_option('jazireh_news_seeded', '1', false);
    }
}
