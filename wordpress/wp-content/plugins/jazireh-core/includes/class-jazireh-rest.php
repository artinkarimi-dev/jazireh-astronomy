<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_REST
{
    const NAMESPACE_NAME = 'jazireh/v1';

    public static function boot()
    {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    public static function register_routes()
    {
        register_rest_route(self::NAMESPACE_NAME, '/news', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'news_index'),
            'permission_callback' => '__return_true',
            'args' => array(
                'limit' => array('sanitize_callback' => 'absint', 'default' => 24),
                'category' => array('sanitize_callback' => 'sanitize_title'),
                'search' => array('sanitize_callback' => 'sanitize_text_field')
            )
        ));

        register_rest_route(self::NAMESPACE_NAME, '/news/(?P<slug>[a-zA-Z0-9-]+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'news_show'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/home', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'home'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/site', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'site'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/apod', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'apod'),
            'permission_callback' => '__return_true',
            'args' => array(
                'limit' => array('sanitize_callback' => 'absint', 'default' => 20)
            )
        ));

        register_rest_route(self::NAMESPACE_NAME, '/sky', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'sky'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/objects', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'objects'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/videos', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'videos'),
            'permission_callback' => '__return_true',
            'args' => array(
                'limit' => array('sanitize_callback' => 'absint', 'default' => 3)
            )
        ));

        register_rest_route(self::NAMESPACE_NAME, '/jazireh-daily', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'daily_index'),
            'permission_callback' => '__return_true',
            'args' => array(
                'limit' => array('sanitize_callback' => 'absint', 'default' => 12)
            )
        ));

        register_rest_route(self::NAMESPACE_NAME, '/jazireh-daily/(?P<slug>[^\/]+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'daily_show'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/jazireh-daily-sync', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array('Jazireh_Daily', 'ingest_request'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/newsletter', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array(__CLASS__, 'newsletter'),
            'permission_callback' => '__return_true'
        ));
    }

    public static function news_index(WP_REST_Request $request)
    {
        $limit = min(max((int) $request->get_param('limit'), 1), 100);
        $args = array(
            'post_type' => Jazireh_News::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => array('relation' => 'OR', array('key' => Jazireh_News::META_FEATURED, 'compare' => 'EXISTS'), array('key' => Jazireh_News::META_FEATURED, 'compare' => 'NOT EXISTS')),
            'orderby' => array('meta_value_num' => 'DESC', 'date' => 'DESC'),
            'no_found_rows' => true
        );
        $category = $request->get_param('category');
        if ($category) {
            $args['tax_query'] = array(array('taxonomy' => Jazireh_News::TAXONOMY, 'field' => 'slug', 'terms' => $category));
        }
        $search = $request->get_param('search');
        if ($search) {
            $args['s'] = $search;
        }
        $query = new WP_Query($args);
        return self::success(array_map(array(__CLASS__, 'format_news'), $query->posts));
    }

    public static function news_show(WP_REST_Request $request)
    {
        $posts = get_posts(array(
            'post_type' => Jazireh_News::POST_TYPE,
            'post_status' => 'publish',
            'name' => sanitize_title($request['slug']),
            'posts_per_page' => 1
        ));
        if (!$posts) {
            return new WP_Error('jazireh_news_not_found', 'خبر پیدا نشد.', array('status' => 404));
        }
        return self::success(self::format_news($posts[0]));
    }

    public static function home()
    {
        $query = new WP_Query(Jazireh_Settings::featured_news_query_args(3));
        $videos = Jazireh_YouTube::latest_videos(3);
        $apod = self::latest_apod_items(1);
        $daily = Jazireh_Daily::latest_posts(1);
        return self::success(array(
            'sky' => self::sky_payload(true),
            'news' => array_map(array(__CLASS__, 'format_news'), $query->posts),
            'videos' => is_wp_error($videos) ? array() : $videos,
            'apod' => is_wp_error($apod) || empty($apod[0]) ? null : $apod[0],
            'dailyPost' => !empty($daily[0]) ? $daily[0] : null,
        ));
    }

    public static function site()
    {
        return self::success(Jazireh_Settings::get_site_payload());
    }

    public static function apod(WP_REST_Request $request)
    {
        $limit = min(max((int) $request->get_param('limit'), 1), 20);
        $items = self::latest_apod_items($limit);
        if (is_wp_error($items)) {
            return $items;
        }
        return self::success($items);
    }

    public static function sky()
    {
        return self::success(self::sky_payload(false));
    }

    public static function videos(WP_REST_Request $request)
    {
        $limit = min(max((int) $request->get_param('limit'), 1), 10);
        $videos = Jazireh_YouTube::latest_videos($limit);
        if (is_wp_error($videos)) {
            return $videos;
        }
        return self::success($videos);
    }

    public static function daily_index(WP_REST_Request $request)
    {
        $limit = min(max((int) $request->get_param('limit'), 1), 24);
        return self::success(Jazireh_Daily::latest_posts($limit));
    }

    public static function daily_show(WP_REST_Request $request)
    {
        $post = Jazireh_Daily::find_by_slug($request['slug']);
        if (!$post) {
            return new WP_Error('jazireh_daily_not_found', 'پست جزیره دیلی پیدا نشد.', array('status' => 404));
        }
        return self::success($post);
    }

    public static function objects()
    {
        return self::success(Jazireh_Objects::list_objects());
    }

    public static function newsletter(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        $result = Jazireh_Newsletter::subscribe(is_array($payload) ? $payload : array());
        if (is_wp_error($result)) {
            $status = (int) ($result->get_error_data()['status'] ?? 400);
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message(),
                'errors' => (array) ($result->get_error_data()['errors'] ?? array()),
            ), $status);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => $result['message'],
            'data' => null,
        ), (int) $result['status']);
    }

    private static function format_news(WP_Post $post)
    {
        $terms = wp_get_post_terms($post->ID, Jazireh_News::TAXONOMY);
        $category = !is_wp_error($terms) && !empty($terms) ? $terms[0]->name : 'نجوم';
        $image = get_the_post_thumbnail_url($post->ID, 'large');
        return array(
            'id' => (int) $post->ID,
            'slug' => $post->post_name,
            'title' => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
            'excerpt' => wp_strip_all_tags($post->post_excerpt ?: wp_trim_words($post->post_content, 32, '…')),
            'content' => wp_kses_post(apply_filters('the_content', $post->post_content)),
            'image' => $image ?: '',
            'category' => $category,
            'readingTime' => get_post_meta($post->ID, Jazireh_News::META_READING_TIME, true) ?: '۵ دقیقه',
            'featured' => get_post_meta($post->ID, Jazireh_News::META_FEATURED, true) === '1',
            'publishedAt' => get_post_time(DATE_ATOM, true, $post)
        );
    }

    private static function sky_payload($for_home = false)
    {
        $settings = get_option(Jazireh_Settings::OPTION_NAME, array());
        $location = self::setting_value($settings, 'default_city', 'تهران');
        if ($location === 'تهران') {
            $location = 'تهران، ایران';
        }

        $payload = array(
            'id' => 1,
            'location' => $location,
            'latitude' => self::setting_value($settings, 'default_latitude', '35.6892'),
            'longitude' => self::setting_value($settings, 'default_longitude', '51.3890'),
            'temperature' => 24,
            'condition' => 'آسمان صاف',
            'humidity' => 32,
            'wind' => '8.00',
            'pressure' => 1016,
            'visibility' => '9.40',
            'moonPhase' => 'هلال افزایشی',
            'moonIllumination' => '29.00',
            'moonAge' => '5.00',
            'sunrise' => '۰۴:۵۶',
            'sunset' => '۱۹:۳۱',
            'bestTime' => '۲۲:۳۰ تا ۰۳:۳۰',
            'seeing' => '7.0',
            'transparency' => '8.5',
            'events' => array(
                array(
                    'title' => 'هم‌نشینی ماه و زهره',
                    'time' => '۱۹:۵۰',
                    'detail' => 'فاصله زاویه‌ای تقریبی ۱٫۸ درجه',
                ),
                array(
                    'title' => 'بارش شهابی اتا دلوی',
                    'time' => '۰۲:۳۰',
                    'detail' => 'بهترین مشاهده در افق جنوب‌شرقی',
                ),
                array(
                    'title' => 'عبور ایستگاه فضایی',
                    'time' => '۲۳:۲۱',
                    'detail' => 'قابل مشاهده برای حدود چهار دقیقه',
                ),
            ),
            'observed_at' => '2026-08-04 15:41:46',
        );

        if ($for_home) {
            unset($payload['id'], $payload['latitude'], $payload['longitude'], $payload['moonAge'], $payload['seeing'], $payload['events']);
        }

        return $payload;
    }

    private static function latest_apod_items($limit)
    {
        $cache_key = 'jazireh_apod_latest_range_' . $limit;
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

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

        $items = array_values(array_map(array(__CLASS__, 'format_apod'), $data));
        usort($items, array(__CLASS__, 'sort_apod_desc'));
        set_transient($cache_key, $items, 6 * HOUR_IN_SECONDS);
        return $items;
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

    private static function format_apod($item)
    {
        $media_type = sanitize_key(isset($item['media_type']) ? $item['media_type'] : 'image');
        $image = '';
        if ($media_type === 'video') {
            $image = isset($item['thumbnail_url']) ? esc_url_raw($item['thumbnail_url']) : '';
        } else {
            $image = isset($item['hdurl']) ? esc_url_raw($item['hdurl']) : (isset($item['url']) ? esc_url_raw($item['url']) : '');
        }
        $content = isset($item['explanation']) ? wp_strip_all_tags($item['explanation']) : '';

        return array(
            'id' => isset($item['date']) ? abs(crc32((string) $item['date'])) : 0,
            'date' => sanitize_text_field(isset($item['date']) ? $item['date'] : ''),
            'title' => html_entity_decode(wp_strip_all_tags(isset($item['title']) ? $item['title'] : 'NASA APOD'), ENT_QUOTES, 'UTF-8'),
            'image' => $image,
            'mediaType' => $media_type,
            'content' => $content,
            'excerpt' => wp_trim_words($content, 32, '...'),
            'photographer' => sanitize_text_field(isset($item['copyright']) ? $item['copyright'] : 'NASA'),
            'sourceUrl' => isset($item['url']) ? esc_url_raw($item['url']) : '',
        );
    }

    private static function sort_apod_desc($left, $right)
    {
        return strcmp((string) $right['date'], (string) $left['date']);
    }

    private static function setting_value($settings, $key, $default = '')
    {
        if (is_array($settings) && isset($settings[$key]) && $settings[$key] !== '') {
            return sanitize_text_field((string) $settings[$key]);
        }
        return $default;
    }

    private static function success($data)
    {
        return rest_ensure_response(array('success' => true, 'data' => $data));
    }
}
