<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_APOD_Service
{
    const WIDGET_KEY = 'apod';
    const CACHE_TTL = 21600;
    const REFRESH_HOOK = 'jazireh_refresh_apod_cache';

    public static function boot()
    {
        add_action(self::REFRESH_HOOK, array(__CLASS__, 'refresh'));
    }

    public static function latest_items($limit, $args = array())
    {
        $limit = self::normalize_limit($limit);
        $force_refresh = !empty($args['forceRefresh']);
        $cache_key = 'jazireh_apod_latest_range_' . $limit;
        $cached = get_transient($cache_key);
        if (!$force_refresh && is_array($cached) && !self::items_need_refresh($cached)) {
            return self::localize_items($cached);
        }

        if (!$force_refresh) {
            $fallback = self::last_good_items();
            if ($fallback) {
                self::schedule_refresh();
                return array_slice($fallback, 0, $limit);
            }
            self::schedule_refresh();
            return new WP_Error('jazireh_apod_cache_empty', 'NASA APOD cache is warming in the background.', array('status' => 503));
        }

        $items = self::fetch_latest_items($limit);
        if (is_wp_error($items)) {
            $fallback = self::last_good_items();
            if ($fallback) {
                return self::localize_items(array_slice($fallback, 0, $limit));
            }
            return $items;
        }

        set_transient($cache_key, $items, self::CACHE_TTL);
        self::queue_latest_localization($items);
        self::store_widget_ready($items);
        return $items;
    }

    public static function cached_items($limit)
    {
        $limit = self::normalize_limit($limit);
        $cache_key = 'jazireh_apod_latest_range_' . $limit;
        $cached = get_transient($cache_key);
        if (is_array($cached) && !self::items_need_refresh($cached)) {
            return self::localize_items($cached);
        }

        $fallback = self::last_good_items();
        if ($fallback) {
            self::schedule_refresh();
            return array_slice($fallback, 0, $limit);
        }

        self::schedule_refresh();
        return array();
    }

    public static function widget($limit = 1, $args = array())
    {
        $limit = self::normalize_limit($limit);
        $force_refresh = !empty($args['forceRefresh']);
        $cache_key = 'jazireh_apod_latest_range_' . $limit;
        $cached = get_transient($cache_key);
        if (!$force_refresh && is_array($cached) && !self::items_need_refresh($cached)) {
            return self::store_widget_ready(self::localize_items($cached));
        }

        if (!$force_refresh) {
            $last_good = Jazireh_Widgets::last_good_as_stale(self::WIDGET_KEY, 'Latest NASA APOD cache is being refreshed; last known good result returned.');
            if (is_array($last_good)) {
                self::schedule_refresh();
                return $last_good;
            }
            self::schedule_refresh();
            return Jazireh_Widgets::error(self::WIDGET_KEY, 'NASA APOD cache is warming in the background.', array(
                'source' => 'NASA APOD',
                'sourceUrl' => 'https://apod.nasa.gov/apod/',
            ));
        }

        $items = self::fetch_latest_items($limit);
        if (is_wp_error($items)) {
            return Jazireh_Widgets::error(self::WIDGET_KEY, $items->get_error_message(), array(
                'source' => 'NASA APOD',
                'sourceUrl' => 'https://apod.nasa.gov/apod/',
            ));
        }

        set_transient($cache_key, $items, self::CACHE_TTL);
        self::queue_latest_localization($items);
        return self::store_widget_ready(self::localize_items($items));
    }

    public static function refresh()
    {
        delete_transient('jazireh_apod_latest_range_1');
        Jazireh_Widgets::delete_cached(self::WIDGET_KEY);
        return self::widget(1, array('forceRefresh' => true));
    }

    public static function cached_home_payload()
    {
        $cached = get_transient('jazireh_apod_latest_range_1');
        if (is_array($cached) && !self::items_need_refresh($cached)) {
            $items = self::localize_items($cached);
            return !empty($items[0]) ? $items[0] : null;
        }

        $fallback = self::last_good_items();
        if ($fallback) {
            self::schedule_refresh();
            $item = $fallback[0];
            if (self::items_need_refresh($fallback)) {
                $item['isFallback'] = true;
                $item['displayWarning'] = 'این APOD آخرین نسخه ذخیره‌شده است و به‌روزرسانی تازه در پس‌زمینه انجام می‌شود.';
            } else {
                $item['isFallback'] = false;
                unset($item['displayWarning']);
            }
            return $item;
        }

        self::schedule_refresh();
        return null;
    }

    public static function home_payload()
    {
        $widget = self::widget(1);
        if (!is_array($widget) || empty($widget['data']) || !is_array($widget['data'])) {
            return null;
        }

        if (!empty($widget['data']['latest']) && is_array($widget['data']['latest'])) {
            return $widget['data']['latest'];
        }

        return !empty($widget['data']['items'][0]) && is_array($widget['data']['items'][0]) ? $widget['data']['items'][0] : null;
    }

    private static function fetch_latest_items($limit)
    {
        $api_key = self::nasa_api_key();
        $end_timestamp = current_time('timestamp', true);
        $end_date = gmdate('Y-m-d', $end_timestamp);
        $start_date = gmdate('Y-m-d', strtotime('-' . ($limit - 1) . ' days', $end_timestamp));
        $url = add_query_arg(array(
            'api_key' => $api_key,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'thumbs' => 'true',
        ), 'https://api.nasa.gov/planetary/apod');

        $response = wp_remote_get($url, array('timeout' => 12));
        if (is_wp_error($response)) {
            return new WP_Error('jazireh_apod_request_failed', $response->get_error_message(), array('status' => 502));
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!empty($data['error']['message'])) {
            return new WP_Error('jazireh_apod_api_error', sanitize_text_field($data['error']['message']), array('status' => 502));
        }
        if ($status < 200 || $status >= 300 || !is_array($data)) {
            return new WP_Error('jazireh_apod_bad_response', 'NASA APOD API returned an invalid response.', array('status' => 502));
        }

        if (isset($data['date'])) {
            $data = array($data);
        }

        $items = array_values(array_map(array(__CLASS__, 'format_item'), $data));
        usort($items, array(__CLASS__, 'sort_desc'));
        return $items;
    }

    private static function store_widget_ready($items)
    {
        $now = current_time('timestamp', true);
        return Jazireh_Widgets::ready(self::WIDGET_KEY, self::data_payload($items), array(
            'updatedAt' => $now,
            'expiresAt' => $now + self::CACHE_TTL,
            'source' => 'NASA APOD',
            'sourceUrl' => 'https://apod.nasa.gov/apod/',
        ));
    }

    private static function queue_latest_localization($items)
    {
        if (class_exists('Jazireh_APOD_Localizer') && is_array($items)) {
            Jazireh_APOD_Localizer::maybe_queue_latest($items);
        }
    }

    private static function data_payload($items)
    {
        return array(
            'latest' => !empty($items[0]) ? $items[0] : null,
            'items' => array_values($items),
        );
    }

    private static function last_good_items()
    {
        $last_good = Jazireh_Widgets::last_good(self::WIDGET_KEY);
        if (!is_array($last_good) || empty($last_good['data']['items']) || !is_array($last_good['data']['items'])) {
            return array();
        }
            return self::localize_items(array_values($last_good['data']['items']));
    }

    private static function items_need_refresh($items)
    {
        if (empty($items[0]['date'])) {
            return true;
        }
        return (string) $items[0]['date'] < gmdate('Y-m-d', current_time('timestamp', true));
    }

    private static function schedule_refresh()
    {
        if (!wp_next_scheduled(self::REFRESH_HOOK)) {
            wp_schedule_single_event(time() + 2 * MINUTE_IN_SECONDS, self::REFRESH_HOOK);
        }
    }

    private static function nasa_api_key()
    {
        $settings = get_option(Jazireh_Settings::OPTION_NAME, array());
        if (is_array($settings) && !empty($settings['nasa_api_key'])) {
            return (string) $settings['nasa_api_key'];
        }
        if (defined('JAZIREH_NASA_API_KEY') && JAZIREH_NASA_API_KEY) {
            return JAZIREH_NASA_API_KEY;
        }
        if (defined('NASA_API_KEY') && NASA_API_KEY) {
            return NASA_API_KEY;
        }
        $env_key = getenv('NASA_API_KEY');
        return $env_key ?: 'DEMO_KEY';
    }

    private static function format_item($item)
    {
        $media_type = sanitize_key(isset($item['media_type']) ? $item['media_type'] : 'image');
        $image = '';
        if ($media_type === 'video') {
            $image = isset($item['thumbnail_url']) ? esc_url_raw($item['thumbnail_url']) : '';
        } else {
            $image = isset($item['url']) ? esc_url_raw($item['url']) : (isset($item['hdurl']) ? esc_url_raw($item['hdurl']) : '');
        }
        $content = isset($item['explanation']) ? wp_strip_all_tags($item['explanation']) : '';

        $title = html_entity_decode(wp_strip_all_tags(isset($item['title']) ? $item['title'] : 'NASA APOD'), ENT_QUOTES, 'UTF-8');
        $date = sanitize_text_field(isset($item['date']) ? $item['date'] : '');

        return self::localize_item(array(
            'id' => isset($item['date']) ? abs(crc32((string) $item['date'])) : 0,
            'date' => $date,
            'title' => $title,
            'titleOriginal' => $title,
            'image' => $image,
            'mediaType' => $media_type,
            'content' => $content,
            'contentOriginal' => $content,
            'excerpt' => wp_trim_words($content, 32, '...'),
            'excerptOriginal' => wp_trim_words($content, 32, '...'),
            'titleFa' => '',
            'summaryFa' => '',
            'contentFa' => '',
            'translationStatus' => 'missing',
            'hasPersianEditorial' => false,
            'localizationWarning' => 'متن اصلی NASA به انگلیسی نمایش داده می‌شود؛ توضیح فارسی این تصویر هنوز آماده نشده است.',
            'photographer' => sanitize_text_field(isset($item['copyright']) ? $item['copyright'] : 'NASA'),
            'sourceUrl' => isset($item['url']) ? esc_url_raw($item['url']) : '',
            'mediaUrl' => isset($item['url']) ? esc_url_raw($item['url']) : '',
            'hdUrl' => isset($item['hdurl']) ? esc_url_raw($item['hdurl']) : '',
            'thumbnailUrl' => isset($item['thumbnail_url']) ? esc_url_raw($item['thumbnail_url']) : '',
            'serviceVersion' => sanitize_text_field(isset($item['service_version']) ? $item['service_version'] : ''),
            'sourceHash' => class_exists('Jazireh_APOD_Editorial') ? Jazireh_APOD_Editorial::source_hash(array(
                'date' => $date,
                'titleOriginal' => $title,
                'contentOriginal' => $content,
                'mediaType' => $media_type,
                'photographer' => sanitize_text_field(isset($item['copyright']) ? $item['copyright'] : 'NASA'),
                'sourceUrl' => isset($item['url']) ? esc_url_raw($item['url']) : '',
                'hdUrl' => isset($item['hdurl']) ? esc_url_raw($item['hdurl']) : '',
                'serviceVersion' => sanitize_text_field(isset($item['service_version']) ? $item['service_version'] : ''),
            )) : '',
        ));
    }

    public static function localize_item(array $item)
    {
        $date = sanitize_text_field((string) ($item['date'] ?? ''));
        $title_original = self::clean_text(($item['titleOriginal'] ?? '') ?: ($item['title'] ?? 'NASA APOD'));
        $content_original = self::clean_text(($item['contentOriginal'] ?? '') ?: ($item['content'] ?? ''));
        $excerpt_original = self::clean_text(($item['excerptOriginal'] ?? '') ?: (($item['excerpt'] ?? '') ?: wp_trim_words($content_original, 32, '...')));

        $item['titleOriginal'] = $title_original;
        $item['contentOriginal'] = $content_original;
        $item['excerptOriginal'] = $excerpt_original;
        $item['sourceHash'] = class_exists('Jazireh_APOD_Editorial') ? Jazireh_APOD_Editorial::source_hash($item) : (string) ($item['sourceHash'] ?? '');
        $item['titleFa'] = '';
        $item['summaryFa'] = '';
        $item['contentFa'] = '';
        $item['translationStatus'] = 'missing';
        $item['translationSourceHash'] = '';
        $item['translatedAt'] = '';
        $item['reviewedAt'] = '';
        $item['hasPersianEditorial'] = false;
        $item['localizationWarning'] = 'متن اصلی NASA به انگلیسی نمایش داده می‌شود؛ توضیح فارسی این تصویر هنوز آماده نشده است.';

        $editorial = class_exists('Jazireh_APOD_Editorial') ? Jazireh_APOD_Editorial::get_ready_for_date($date, $item['sourceHash']) : null;
        if (is_array($editorial) && (!empty($editorial['titleFa']) || !empty($editorial['summaryFa']) || !empty($editorial['contentFa']))) {
            $item['titleFa'] = $editorial['titleFa'];
            $item['summaryFa'] = $editorial['summaryFa'];
            $item['contentFa'] = $editorial['contentFa'];
            $item['translationStatus'] = sanitize_key((string) ($editorial['translationStatus'] ?? 'ready'));
            $item['translationSourceHash'] = sanitize_text_field((string) ($editorial['translationSourceHash'] ?? ''));
            $item['translatedAt'] = sanitize_text_field((string) ($editorial['translatedAt'] ?? ''));
            $item['reviewedAt'] = sanitize_text_field((string) ($editorial['reviewedAt'] ?? ''));
            $item['hasPersianEditorial'] = true;
            $item['editorialId'] = (int) ($editorial['editorialId'] ?? 0);
            $item['localizationWarning'] = '';

            if (!empty($editorial['titleFa'])) {
                $item['title'] = $editorial['titleFa'];
            }
            if (!empty($editorial['contentFa'])) {
                $item['content'] = $editorial['contentFa'];
            }
            if (!empty($editorial['summaryFa'])) {
                $item['excerpt'] = $editorial['summaryFa'];
            } elseif (!empty($editorial['contentFa'])) {
                $item['excerpt'] = wp_trim_words(wp_strip_all_tags($editorial['contentFa']), 32, '...');
            }
        } else {
            $item['title'] = $title_original;
            $item['content'] = $content_original;
            $item['excerpt'] = $excerpt_original;
            $stale = class_exists('Jazireh_APOD_Editorial') && Jazireh_APOD_Editorial::find_id_by_date($date);
            if ($stale) {
                $item['translationStatus'] = 'pending';
                $item['localizationWarning'] = 'ترجمه فارسی برای نسخه تازه NASA در حال آماده‌سازی است؛ تا تکمیل ترجمه معتبر، متن اصلی NASA نمایش داده می‌شود.';
            }
        }

        return $item;
    }

    public static function localize_items(array $items)
    {
        return array_values(array_map(array(__CLASS__, 'localize_item'), $items));
    }

    private static function sort_desc($left, $right)
    {
        return strcmp((string) $right['date'], (string) $left['date']);
    }

    private static function normalize_limit($limit)
    {
        return min(max((int) $limit, 1), 20);
    }

    private static function clean_text($text)
    {
        return html_entity_decode(wp_strip_all_tags((string) $text), ENT_QUOTES, 'UTF-8');
    }
}
