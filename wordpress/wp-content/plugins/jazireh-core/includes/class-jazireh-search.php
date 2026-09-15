<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Search
{
    const MIN_QUERY_LENGTH = 2;
    const MAX_QUERY_LENGTH = 80;
    const SOURCE_LIMIT = 50;
    const DEFAULT_PER_PAGE = 10;
    const MAX_PER_PAGE = 20;

    public static function search(WP_REST_Request $request)
    {
        $raw_query = self::sanitize_query($request->get_param('q'));
        $normalized_query = self::normalize($raw_query);
        $page = max(1, (int) $request->get_param('page'));
        $per_page = min(max((int) $request->get_param('per_page'), 1), self::MAX_PER_PAGE);

        if ($per_page < 1) {
            $per_page = self::DEFAULT_PER_PAGE;
        }

        if (self::query_too_short($normalized_query)) {
            return self::empty_response($raw_query, $page, $per_page);
        }

        $groups = array(
            'news' => self::search_posts(
                Jazireh_News::POST_TYPE,
                'news',
                'اخبار علمی',
                '/news/',
                $normalized_query,
                array(__CLASS__, 'format_news_result')
            ),
            'videos' => self::search_videos($normalized_query),
            'apod' => self::search_apod($normalized_query),
            'daily' => self::search_posts(
                Jazireh_Daily::POST_TYPE,
                'daily',
                'جزیره دیلی',
                '/jazireh-daily/',
                $normalized_query,
                array(__CLASS__, 'format_daily_result')
            ),
            'objects' => self::search_objects($normalized_query),
            'events' => self::search_events($normalized_query),
            'topics' => Jazireh_Topics::search($normalized_query),
        );

        $flat = array();
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $flat[] = $item;
            }
        }
        usort($flat, array(__CLASS__, 'sort_results'));

        $total = count($flat);
        $offset = ($page - 1) * $per_page;

        return array(
            'query' => $raw_query,
            'normalizedQuery' => $normalized_query,
            'total' => $total,
            'page' => $page,
            'perPage' => $per_page,
            'hasMore' => ($offset + $per_page) < $total,
            'results' => array_slice($flat, $offset, $per_page),
            'groups' => $groups,
        );
    }

    private static function search_posts($post_type, $type, $type_label, $url_prefix, $normalized_query, $formatter)
    {
        $query = new WP_Query(array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => self::SOURCE_LIMIT,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ));

        $items = array();
        foreach ($query->posts as $post) {
            $haystack = self::normalize(get_the_title($post) . ' ' . $post->post_excerpt . ' ' . wp_strip_all_tags($post->post_content));
            if (!self::contains($haystack, $normalized_query)) {
                continue;
            }
            $items[] = call_user_func($formatter, $post, $type, $type_label, $url_prefix);
        }

        return self::group($type, $type_label, $items);
    }

    private static function search_videos($normalized_query)
    {
        $videos = Jazireh_YouTube::latest_videos(10);
        if (is_wp_error($videos)) {
            return self::group('videos', 'ویدیوها', array());
        }

        $items = array();
        foreach ($videos as $video) {
            $haystack = self::normalize(($video['title'] ?? '') . ' ' . ($video['description'] ?? '') . ' ' . ($video['channelTitle'] ?? ''));
            if (!self::contains($haystack, $normalized_query)) {
                continue;
            }

            $items[] = array(
                'id' => (string) ($video['id'] ?? ''),
                'type' => 'videos',
                'typeLabel' => 'ویدیوها',
                'title' => self::clean_text($video['title'] ?? 'ویدیوی جزیره'),
                'excerpt' => self::excerpt($video['description'] ?? ''),
                'url' => '/videos',
                'sourceUrl' => esc_url_raw((string) ($video['youtubeUrl'] ?? '')),
                'thumbnail' => esc_url_raw((string) ($video['poster'] ?? '')),
                'publishedAt' => sanitize_text_field((string) ($video['publishedAt'] ?? '')),
            );
        }

        return self::group('videos', 'ویدیوها', $items);
    }

    private static function search_apod($normalized_query)
    {
        $apod = Jazireh_APOD_Service::latest_items(20);
        if (is_wp_error($apod)) {
            return self::group('apod', 'تصویر روز ناسا', array());
        }

        $items = array();
        foreach ($apod as $item) {
            $haystack = self::normalize(
                ($item['title'] ?? '') . ' ' .
                ($item['excerpt'] ?? '') . ' ' .
                ($item['content'] ?? '') . ' ' .
                ($item['titleFa'] ?? '') . ' ' .
                ($item['summaryFa'] ?? '') . ' ' .
                ($item['contentFa'] ?? '') . ' ' .
                ($item['titleOriginal'] ?? '') . ' ' .
                ($item['excerptOriginal'] ?? '') . ' ' .
                ($item['contentOriginal'] ?? '') . ' ' .
                ($item['photographer'] ?? '')
            );
            if (!self::contains($haystack, $normalized_query)) {
                continue;
            }

            $title = !empty($item['hasPersianEditorial']) && !empty($item['titleFa'])
                ? $item['titleFa']
                : ($item['titleOriginal'] ?? ($item['title'] ?? 'NASA APOD'));
            $excerpt = !empty($item['hasPersianEditorial'])
                ? (($item['summaryFa'] ?? '') ?: ($item['contentFa'] ?? ''))
                : (($item['excerptOriginal'] ?? '') ?: ($item['contentOriginal'] ?? ($item['excerpt'] ?? '')));

            $items[] = array(
                'id' => (string) ($item['id'] ?? ''),
                'type' => 'apod',
                'typeLabel' => 'تصویر روز ناسا',
                'title' => self::clean_text($title),
                'excerpt' => self::excerpt($excerpt),
                'url' => '/apod',
                'sourceUrl' => esc_url_raw((string) ($item['sourceUrl'] ?? '')),
                'thumbnail' => esc_url_raw((string) ($item['image'] ?? '')),
                'publishedAt' => sanitize_text_field((string) ($item['date'] ?? '')),
                'hasPersianEditorial' => !empty($item['hasPersianEditorial']),
                'translationStatus' => sanitize_key((string) ($item['translationStatus'] ?? 'missing')),
            );
        }

        return self::group('apod', 'تصویر روز ناسا', $items);
    }

    private static function search_objects($normalized_query)
    {
        $objects = Jazireh_Objects::list_objects(array('limit' => self::SOURCE_LIMIT));
        $items = array();

        foreach ($objects as $object) {
            $facts = is_array($object['facts'] ?? null) ? implode(' ', $object['facts']) : '';
            $stats = is_array($object['stats'] ?? null) ? implode(' ', array_values($object['stats'])) : '';
            $haystack = self::normalize(($object['name'] ?? '') . ' ' . ($object['nameEn'] ?? '') . ' ' . ($object['type'] ?? '') . ' ' . $facts . ' ' . $stats);
            if (!self::contains($haystack, $normalized_query)) {
                continue;
            }

            $items[] = array(
                'id' => (string) ($object['id'] ?? ''),
                'type' => 'objects',
                'typeLabel' => 'اجرام آسمانی',
                'title' => self::clean_text($object['name'] ?? ''),
                'excerpt' => self::excerpt(($object['type'] ?? '') . ' ' . $facts),
                'url' => '/explore',
                'sourceUrl' => '',
                'thumbnail' => '',
                'publishedAt' => '',
            );
        }

        return self::group('objects', 'اجرام آسمانی', $items);
    }

    private static function search_events($normalized_query)
    {
        $query = new WP_Query(array(
            'post_type' => Jazireh_Events::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => self::SOURCE_LIMIT,
            'meta_key' => Jazireh_Events::META_START_DATE,
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'no_found_rows' => true,
        ));

        $types = Jazireh_Events::event_types();
        $items = array();
        foreach ($query->posts as $post) {
            $event = Jazireh_Events::format_event($post);
            $haystack = self::normalize(
                ($event['title'] ?? '') . ' ' .
                ($event['description'] ?? '') . ' ' .
                ($event['visibility'] ?? '') . ' ' .
                ($event['eventTypeLabel'] ?? '') . ' ' .
                ($event['source']['label'] ?? '')
            );
            if (!self::contains($haystack, $normalized_query)) {
                continue;
            }

            $items[] = array(
                'id' => (string) ($event['id'] ?? ''),
                'type' => 'events',
                'typeLabel' => 'رویدادهای نجومی',
                'title' => self::clean_text($event['title'] ?? ''),
                'excerpt' => self::excerpt(($event['eventTypeLabel'] ?? '') . ' ' . ($event['description'] ?? '') . ' ' . ($event['visibility'] ?? '')),
                'url' => (string) ($event['url'] ?? '/events'),
                'sourceUrl' => esc_url_raw((string) ($event['source']['url'] ?? '')),
                'thumbnail' => esc_url_raw((string) ($event['image'] ?? '')),
                'publishedAt' => sanitize_text_field((string) (($event['startDate'] ?? '') ?: ($event['publishedAt'] ?? ''))),
                'status' => sanitize_key((string) ($event['status'] ?? 'upcoming')),
                'eventType' => sanitize_key((string) ($event['eventType'] ?? 'other')),
                'eventTypeLabel' => self::clean_text($types[$event['eventType'] ?? 'other'] ?? 'رویداد نجومی'),
            );
        }

        return self::group('events', 'رویدادهای نجومی', $items);
    }

    private static function format_news_result(WP_Post $post, $type, $type_label, $url_prefix)
    {
        $result = self::format_post_result($post, $type, $type_label, $url_prefix);
        $result['sourceUrl'] = esc_url_raw((string) get_post_meta($post->ID, Jazireh_News::META_SOURCE_URL, true));
        return $result;
    }

    private static function format_daily_result(WP_Post $post, $type, $type_label, $url_prefix)
    {
        $thumbnail = get_the_post_thumbnail_url($post->ID, 'medium');
        if (!$thumbnail) {
            $image_ids = get_post_meta($post->ID, Jazireh_Daily::META_IMAGE_IDS, true);
            if (is_array($image_ids) && !empty($image_ids[0])) {
                $image = wp_get_attachment_image_src((int) $image_ids[0], 'medium');
                $thumbnail = $image ? $image[0] : '';
            }
        }

        return self::format_post_result($post, $type, $type_label, $url_prefix, $thumbnail);
    }

    private static function format_post_result(WP_Post $post, $type, $type_label, $url_prefix, $thumbnail = null)
    {
        if ($thumbnail === null) {
            $thumbnail = get_the_post_thumbnail_url($post->ID, 'medium');
        }

        return array(
            'id' => (string) $post->ID,
            'type' => $type,
            'typeLabel' => $type_label,
            'title' => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
            'excerpt' => self::excerpt($post->post_excerpt ?: wp_strip_all_tags($post->post_content)),
            'url' => $url_prefix . $post->post_name,
            'sourceUrl' => '',
            'thumbnail' => esc_url_raw((string) $thumbnail),
            'publishedAt' => get_post_time(DATE_ATOM, true, $post),
        );
    }

    private static function group($type, $label, array $items)
    {
        return array(
            'type' => $type,
            'label' => $label,
            'total' => count($items),
            'items' => array_values($items),
        );
    }

    private static function empty_response($query, $page, $per_page)
    {
        $groups = array(
            'news' => self::group('news', 'اخبار علمی', array()),
            'videos' => self::group('videos', 'ویدیوها', array()),
            'apod' => self::group('apod', 'تصویر روز ناسا', array()),
            'daily' => self::group('daily', 'جزیره دیلی', array()),
            'objects' => self::group('objects', 'اجرام آسمانی', array()),
            'events' => self::group('events', 'رویدادهای نجومی', array()),
            'topics' => self::group('topics', 'پرونده‌های علمی', array()),
        );

        return array(
            'query' => $query,
            'normalizedQuery' => self::normalize($query),
            'total' => 0,
            'page' => $page,
            'perPage' => $per_page,
            'hasMore' => false,
            'results' => array(),
            'groups' => $groups,
        );
    }

    private static function sanitize_query($query)
    {
        $query = sanitize_text_field(wp_unslash((string) $query));
        $query = preg_replace('/\s+/u', ' ', $query);
        if (function_exists('mb_substr')) {
            return trim(mb_substr($query, 0, self::MAX_QUERY_LENGTH, 'UTF-8'));
        }
        return trim(substr($query, 0, self::MAX_QUERY_LENGTH));
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

    private static function contains($haystack, $needle)
    {
        if ($needle === '') {
            return false;
        }
        if (function_exists('mb_stripos')) {
            if (mb_stripos($haystack, $needle, 0, 'UTF-8') !== false) {
                return true;
            }
            return mb_stripos(self::compact($haystack), self::compact($needle), 0, 'UTF-8') !== false;
        }
        return stripos($haystack, $needle) !== false || stripos(self::compact($haystack), self::compact($needle)) !== false;
    }

    private static function query_too_short($query)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($query, 'UTF-8') < self::MIN_QUERY_LENGTH;
        }
        return strlen($query) < self::MIN_QUERY_LENGTH;
    }

    private static function excerpt($text)
    {
        return self::clean_text(wp_trim_words(wp_strip_all_tags((string) $text), 28, '...'));
    }

    private static function compact($value)
    {
        return preg_replace('/\s+/u', '', (string) $value);
    }

    private static function clean_text($text)
    {
        return html_entity_decode(wp_strip_all_tags((string) $text), ENT_QUOTES, 'UTF-8');
    }

    private static function sort_results($left, $right)
    {
        return strcmp((string) ($right['publishedAt'] ?? ''), (string) ($left['publishedAt'] ?? ''));
    }
}


