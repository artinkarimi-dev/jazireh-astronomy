<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Topics
{
    const TAXONOMY = 'jazireh_topic';

    public static function boot()
    {
        add_action('init', array(__CLASS__, 'register_taxonomy'));
    }

    public static function register_taxonomy()
    {
        $post_types = array(Jazireh_News::POST_TYPE, Jazireh_Events::POST_TYPE, Jazireh_APOD_Editorial::POST_TYPE);
        if (post_type_exists(Jazireh_Daily::POST_TYPE)) {
            $post_types[] = Jazireh_Daily::POST_TYPE;
        }

        register_taxonomy(self::TAXONOMY, $post_types, array(
            'labels' => array(
                'name' => 'موضوعات علمی',
                'singular_name' => 'موضوع علمی',
                'search_items' => 'جستجو در موضوعات',
                'all_items' => 'همه موضوعات',
                'edit_item' => 'ویرایش موضوع',
                'update_item' => 'به‌روزرسانی موضوع',
                'add_new_item' => 'افزودن موضوع',
                'new_item_name' => 'نام موضوع جدید',
                'menu_name' => 'موضوعات علمی',
            ),
            'public' => true,
            'show_in_rest' => true,
            'hierarchical' => false,
            'rewrite' => false,
            'show_admin_column' => true,
        ));
    }

    public static function all()
    {
        return array_values(array_map(array(__CLASS__, 'build_topic'), array_keys(self::definitions())));
    }

    public static function find($slug)
    {
        $slug = sanitize_title((string) $slug);
        $definitions = self::definitions();
        if (!isset($definitions[$slug])) {
            return null;
        }

        return self::build_topic($slug, true);
    }

    public static function search($normalized_query)
    {
        $items = array();
        foreach (self::all() as $topic) {
            $haystack = self::normalize($topic['title'] . ' ' . $topic['summary'] . ' ' . implode(' ', $topic['keywords']));
            if (strpos($haystack, $normalized_query) === false && strpos(self::compact($haystack), self::compact($normalized_query)) === false) {
                continue;
            }

            $items[] = array(
                'id' => $topic['slug'],
                'type' => 'topics',
                'typeLabel' => 'پرونده علمی',
                'title' => $topic['title'],
                'excerpt' => $topic['summary'],
                'url' => '/topics/' . $topic['slug'],
                'sourceUrl' => '',
                'thumbnail' => '',
                'publishedAt' => '',
            );
        }

        return array(
            'type' => 'topics',
            'label' => 'پرونده‌های علمی',
            'total' => count($items),
            'items' => $items,
        );
    }

    private static function build_topic($slug, $include_related = false)
    {
        $definition = self::definitions()[$slug];
        $topic = array_merge($definition, array(
            'slug' => $slug,
            'url' => '/topics/' . $slug,
            'description' => $definition['description'] ?? $definition['summary'],
        ));

        if ($include_related) {
            $topic['related'] = self::related_payload($definition);
        } else {
            $topic['relatedCounts'] = self::related_counts($definition);
        }

        return $topic;
    }

    private static function related_payload($definition)
    {
        return array(
            'news' => self::related_news($definition, 6),
            'videos' => self::related_videos($definition, 4),
            'apod' => self::related_apod($definition, 4),
            'events' => self::related_events($definition, 4),
            'objects' => self::related_objects($definition, 4),
        );
    }

    private static function related_counts($definition)
    {
        $related = array(
            'news' => self::related_news($definition, 6),
            'videos' => self::related_videos($definition, 4),
            'apod' => self::related_apod($definition, 4),
            'events' => self::related_events($definition, 4),
            'objects' => self::related_objects($definition, 4),
        );
        return array(
            'news' => count($related['news']),
            'videos' => count($related['videos']),
            'apod' => count($related['apod']),
            'events' => count($related['events']),
            'objects' => count($related['objects']),
        );
    }

    private static function related_news($definition, $limit)
    {
        $posts = get_posts(array(
            'post_type' => Jazireh_News::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        $items = array();
        foreach ($posts as $post) {
            if (!self::matches($definition, get_the_title($post) . ' ' . $post->post_excerpt . ' ' . wp_strip_all_tags($post->post_content))) {
                continue;
            }
            $items[] = self::format_post_card($post, '/news/');
            if (count($items) >= $limit) {
                break;
            }
        }
        return $items;
    }

    private static function related_videos($definition, $limit)
    {
        $videos = self::cached_youtube_videos(10);

        $items = array();
        foreach ($videos as $video) {
            if (!self::matches($definition, ($video['title'] ?? '') . ' ' . ($video['description'] ?? ''))) {
                continue;
            }
            $items[] = array(
                'id' => sanitize_text_field((string) ($video['id'] ?? '')),
                'title' => self::clean($video['title'] ?? 'ویدیوی جزیره'),
                'excerpt' => self::excerpt($video['description'] ?? ''),
                'url' => '/videos',
                'sourceUrl' => esc_url_raw((string) ($video['youtubeUrl'] ?? '')),
                'thumbnail' => esc_url_raw((string) ($video['poster'] ?? ($video['thumbnail'] ?? ''))),
                'publishedAt' => sanitize_text_field((string) ($video['publishedAt'] ?? '')),
            );
            if (count($items) >= $limit) {
                break;
            }
        }
        return $items;
    }

    private static function related_apod($definition, $limit)
    {
        $apod = self::cached_apod_items(20);

        $items = array();
        foreach ($apod as $item) {
            $haystack = ($item['title'] ?? '') . ' ' . ($item['excerpt'] ?? '') . ' ' . ($item['content'] ?? '') . ' ' . ($item['titleOriginal'] ?? '') . ' ' . ($item['contentOriginal'] ?? '');
            if (!self::matches($definition, $haystack)) {
                continue;
            }
            $items[] = array(
                'id' => sanitize_text_field((string) ($item['id'] ?? '')),
                'title' => self::clean(($item['titleFa'] ?? '') ?: (($item['titleOriginal'] ?? '') ?: ($item['title'] ?? 'NASA APOD'))),
                'excerpt' => self::excerpt(($item['summaryFa'] ?? '') ?: (($item['excerptOriginal'] ?? '') ?: ($item['excerpt'] ?? ''))),
                'url' => '/apod',
                'sourceUrl' => esc_url_raw((string) ($item['sourceUrl'] ?? '')),
                'thumbnail' => esc_url_raw((string) ($item['image'] ?? '')),
                'publishedAt' => sanitize_text_field((string) ($item['date'] ?? '')),
            );
            if (count($items) >= $limit) {
                break;
            }
        }
        return $items;
    }

    private static function cached_youtube_videos($limit)
    {
        $limit = min(max((int) $limit, 1), 10);
        foreach (array(Jazireh_YouTube::CACHE_PREFIX . $limit, Jazireh_YouTube::LAST_GOOD_PREFIX . $limit) as $key) {
            $cached = get_transient($key);
            if (is_array($cached)) {
                return $cached;
            }
        }
        return array();
    }

    private static function cached_apod_items($limit)
    {
        $limit = min(max((int) $limit, 1), 20);
        $cached = get_transient('jazireh_apod_latest_range_' . $limit);
        return is_array($cached) ? $cached : array();
    }

    private static function related_events($definition, $limit)
    {
        $events = Jazireh_Events::query_events(array('status' => 'upcoming', 'per_page' => 24));
        $source = is_array($events['items'] ?? null) ? $events['items'] : array();
        $items = array();
        foreach ($source as $event) {
            if (!self::matches($definition, ($event['title'] ?? '') . ' ' . ($event['description'] ?? '') . ' ' . ($event['eventTypeLabel'] ?? ''))) {
                continue;
            }
            $items[] = $event;
            if (count($items) >= $limit) {
                break;
            }
        }
        return $items;
    }

    private static function related_objects($definition, $limit)
    {
        $objects = Jazireh_Objects::list_objects(array('limit' => 20));
        $items = array();
        foreach ($objects as $object) {
            $facts = is_array($object['facts'] ?? null) ? implode(' ', $object['facts']) : '';
            if (!self::matches($definition, ($object['name'] ?? '') . ' ' . ($object['nameEn'] ?? '') . ' ' . ($object['type'] ?? '') . ' ' . $facts)) {
                continue;
            }
            $items[] = $object;
            if (count($items) >= $limit) {
                break;
            }
        }
        return $items;
    }

    private static function format_post_card(WP_Post $post, $prefix)
    {
        return array(
            'id' => (int) $post->ID,
            'title' => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
            'excerpt' => self::excerpt($post->post_excerpt ?: wp_strip_all_tags($post->post_content)),
            'url' => $prefix . $post->post_name,
            'thumbnail' => esc_url_raw((string) get_the_post_thumbnail_url($post->ID, 'medium')),
            'publishedAt' => get_post_time(DATE_ATOM, true, $post),
            'sourceName' => get_post_meta($post->ID, Jazireh_News::META_SOURCE_NAME, true) ?: '',
        );
    }

    private static function matches($definition, $text)
    {
        $haystack = self::normalize($text);
        foreach ($definition['keywords'] as $keyword) {
            $needle = self::normalize($keyword);
            if ($needle !== '' && (strpos($haystack, $needle) !== false || strpos(self::compact($haystack), self::compact($needle)) !== false)) {
                return true;
            }
        }
        return false;
    }

    private static function definitions()
    {
        return array(
            'moon' => array(
                'title' => 'ماه',
                'eyebrow' => 'Moon',
                'subtitle' => 'از فاز امشب تا ماموریت‌های آینده',
                'description' => 'فازها، رصد، ماموریت‌ها و داده‌های محاسباتی ماه در جزیره.',
                'summary' => 'پرونده ماه، داده‌های رصدی و محتوای آموزشی مرتبط با تنها قمر زمین را یکجا جمع می‌کند.',
                'phase' => 'Phase 2 topic hub foundation',
                'keywords' => array('ماه', 'لunar', 'lunar', 'moon', 'ماه‌نشین', 'گرفت', 'هلال', 'بدر'),
                'featuredWidget' => 'moon',
                'featuredRoute' => '/sky',
                'featuredLabel' => 'آسمان امروز',
                'accent' => '#c4b5fd',
            ),
            'sun' => array(
                'title' => 'خورشید',
                'eyebrow' => 'Sun',
                'subtitle' => 'ستاره نزدیک ما و آب‌وهوای فضایی',
                'description' => 'خورشید اکنون، تصویرهای رصدی معتبر و توضیح علمی فعالیت‌های خورشیدی.',
                'summary' => 'پرونده خورشید، تصویرهای نزدیک به زمان واقعی، خبرها و توضیح‌های علمی مرتبط با فعالیت خورشیدی را کنار هم می‌آورد.',
                'phase' => 'Phase 2 topic hub foundation',
                'keywords' => array('خورشید', 'solar', 'sun', 'SDO', 'Helioviewer', 'زبانه', 'تاج خورشیدی'),
                'featuredWidget' => 'sun',
                'featuredRoute' => '/',
                'featuredLabel' => 'خورشید اکنون',
                'accent' => '#f6c84f',
            ),
            'mars' => array(
                'title' => 'مریخ',
                'eyebrow' => 'Mars',
                'subtitle' => 'سیاره سرخ، کاوشگرها و جستجوی نشانه‌های گذشته',
                'description' => 'ماموریت‌ها، سطح مریخ، شرایط رصد و خبرهای سیاره سرخ.',
                'summary' => 'پرونده مریخ محتوای مربوط به ماموریت‌ها، سطح سیاره، کاوشگرها و جایگاه آن در آموزش منظومه شمسی را گردآوری می‌کند.',
                'phase' => 'Phase 2 topic hub foundation',
                'keywords' => array('مریخ', 'mars', 'سیاره سرخ', 'کاوشگر', 'روور'),
                'featuredWidget' => 'object',
                'featuredRoute' => '/explore',
                'featuredLabel' => 'کاوش منظومه شمسی',
                'accent' => '#fb923c',
            ),
            'james-webb' => array(
                'title' => 'تلسکوپ جیمز وب',
                'eyebrow' => 'JWST',
                'subtitle' => 'تصویرهای فروسرخ از آغاز کیهان تا زایش ستاره‌ها',
                'description' => 'رصدهای فروسرخ، کهکشان‌های دور و تصویرهای علمی تلسکوپ فضایی جیمز وب.',
                'summary' => 'پرونده جیمز وب خبرها، تصویرها و توضیح‌های علمی مرتبط با JWST را برای مخاطب فارسی دسته‌بندی می‌کند.',
                'phase' => 'Phase 2 topic hub foundation',
                'keywords' => array('جیمز وب', 'وب', 'JWST', 'James Webb', 'تلسکوپ فضایی', 'فروسرخ'),
                'featuredWidget' => 'apod',
                'featuredRoute' => '/apod',
                'featuredLabel' => 'عکس روز ناسا',
                'accent' => '#7dd3fc',
            ),
            'earth' => array(
                'title' => 'زمین',
                'eyebrow' => 'Earth',
                'subtitle' => 'سیاره زنده، تصویرهای فضایی و رخدادهای زمینی',
                'description' => 'زمین از فضا، رخدادهای زمین‌لرزه و ارتباط سیاره ما با رصد آسمان.',
                'summary' => 'پرونده زمین، داده‌های EPIC، زمین‌لرزه‌ها و محتوای علمی مرتبط با سیاره خانه ما را پیوند می‌دهد.',
                'phase' => 'Phase 2 topic hub foundation',
                'keywords' => array('زمین', 'earth', 'EPIC', 'DSCOVR', 'زلزله', 'زمین‌لرزه', 'محیط‌زیست'),
                'featuredWidget' => 'earth',
                'featuredRoute' => '/sky',
                'featuredLabel' => 'آسمان امروز',
                'accent' => '#38bdf8',
            ),
        );
    }

    private static function normalize($value)
    {
        $value = wp_strip_all_tags((string) $value);
        $value = str_replace(array('ي', 'ى', 'ك', 'ۀ', 'ة'), array('ی', 'ی', 'ک', 'ه', 'ه'), $value);
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value);
        $value = preg_replace('/\x{200c}+/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = trim($value);
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private static function compact($value)
    {
        return preg_replace('/\s+/u', '', (string) $value);
    }

    private static function excerpt($text)
    {
        return self::clean(wp_trim_words(wp_strip_all_tags((string) $text), 28, '...'));
    }

    private static function clean($text)
    {
        return html_entity_decode(wp_strip_all_tags((string) $text), ENT_QUOTES, 'UTF-8');
    }
}
