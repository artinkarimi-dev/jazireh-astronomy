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

        register_rest_route(self::NAMESPACE_NAME, '/widgets', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'widgets'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/widgets/apod', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'widget_apod'),
            'permission_callback' => '__return_true',
            'args' => array(
                'limit' => array('sanitize_callback' => 'absint', 'default' => 1)
            )
        ));

        register_rest_route(self::NAMESPACE_NAME, '/widgets/sun', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'widget_sun'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/widgets/moon', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'widget_moon'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/widgets/sky', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'widget_sky'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/widgets/earth', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'widget_earth'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/widgets/earthquakes', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'widget_earthquakes'),
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

        register_rest_route(self::NAMESPACE_NAME, '/planets', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'planets'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/search', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'search'),
            'permission_callback' => '__return_true',
            'args' => array(
                'q' => array('sanitize_callback' => 'sanitize_text_field', 'default' => ''),
                'page' => array('sanitize_callback' => 'absint', 'default' => 1),
                'per_page' => array('sanitize_callback' => 'absint', 'default' => 10)
            )
        ));

        register_rest_route(self::NAMESPACE_NAME, '/events', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'events'),
            'permission_callback' => '__return_true',
            'args' => array(
                'limit' => array('sanitize_callback' => 'absint'),
                'days' => array('sanitize_callback' => 'absint'),
                'status' => array('sanitize_callback' => 'sanitize_key', 'default' => 'upcoming'),
                'type' => array('sanitize_callback' => 'sanitize_key', 'default' => ''),
                'page' => array('sanitize_callback' => 'absint', 'default' => 1),
                'per_page' => array('sanitize_callback' => 'absint', 'default' => 10)
            )
        ));

        register_rest_route(self::NAMESPACE_NAME, '/events/today', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'events_today'),
            'permission_callback' => '__return_true',
            'args' => array(
                'limit' => array('sanitize_callback' => 'absint', 'default' => 6)
            )
        ));

        register_rest_route(self::NAMESPACE_NAME, '/topics', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'topics'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/topics/(?P<slug>[a-zA-Z0-9-]+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'topic_show'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route(self::NAMESPACE_NAME, '/sun', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'sun'),
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
        $videos = Jazireh_YouTube::cached_videos(6);
        $apod = Jazireh_APOD_Service::cached_home_payload();
        $daily = Jazireh_Daily::latest_posts(1);
        return self::success(array(
            'sky' => self::home_sky_payload(),
            'sun' => self::home_sun_payload(),
            'liveWidgets' => array(
                'sun' => Jazireh_Widgets::cached_or_last_good('sun', 'Fresh solar imagery is being refreshed; last known good result returned.'),
                'moon' => Jazireh_Moon_Service::widget(),
                'earth' => Jazireh_Widgets::cached_or_last_good('earth', 'Fresh NASA EPIC imagery is being refreshed; last known good result returned.'),
            ),
            'news' => array_map(array(__CLASS__, 'format_news'), $query->posts),
            'videos' => $videos,
            'apod' => $apod,
            'dailyPost' => !empty($daily[0]) ? $daily[0] : null,
        ));
    }

    public static function site()
    {
        return self::success(Jazireh_Settings::get_site_payload());
    }

    public static function widgets()
    {
        return self::success(Jazireh_Widgets::registry_payload());
    }

    public static function widget_apod(WP_REST_Request $request)
    {
        $limit = min(max((int) $request->get_param('limit'), 1), 20);
        return self::success(Jazireh_APOD_Service::widget($limit));
    }

    public static function widget_sun()
    {
        return self::success(Jazireh_Sun_Service::widget());
    }

    public static function widget_moon()
    {
        return self::success(Jazireh_Moon_Service::widget());
    }

    public static function widget_sky()
    {
        return self::success(Jazireh_Sky_Service::widget());
    }

    public static function widget_earth()
    {
        return self::success(Jazireh_Earth_Service::widget());
    }

    public static function widget_earthquakes()
    {
        return self::success(Jazireh_Earthquake_Service::widget());
    }

    public static function apod(WP_REST_Request $request)
    {
        $limit = min(max((int) $request->get_param('limit'), 1), 20);
        $items = Jazireh_APOD_Service::cached_items($limit);
        return self::success($items);
    }

    public static function sky()
    {
        return self::success(Jazireh_Sky_Service::sky_today_payload());
    }

    public static function planets()
    {
        return self::success(Jazireh_Planets::payload());
    }

    public static function search(WP_REST_Request $request)
    {
        return self::success(Jazireh_Search::search($request));
    }

    public static function events(WP_REST_Request $request)
    {
        $has_science_params = $request->get_param('limit') !== null || $request->get_param('days') !== null;
        if ($has_science_params) {
            $limit = min(max((int) ($request->get_param('limit') ?: 12), 1), 24);
            $days = min(max((int) ($request->get_param('days') ?: 90), 7), 365);
            return self::success(Jazireh_Events::payload($limit, $days));
        }

        return self::success(Jazireh_Events::query_events(array(
            'status' => $request->get_param('status'),
            'type' => $request->get_param('type'),
            'page' => $request->get_param('page'),
            'per_page' => $request->get_param('per_page'),
        )));
    }

    public static function events_today(WP_REST_Request $request)
    {
        $limit = min(max((int) $request->get_param('limit'), 1), 8);
        return self::success(Jazireh_Events::today($limit));
    }

    public static function topics()
    {
        return self::success(Jazireh_Topics::all());
    }

    public static function topic_show(WP_REST_Request $request)
    {
        $topic = Jazireh_Topics::find($request['slug']);
        if (!$topic) {
            return new WP_Error('jazireh_topic_not_found', 'پرونده علمی پیدا نشد.', array('status' => 404));
        }
        return self::success($topic);
    }

    public static function sun()
    {
        return self::success(Jazireh_Sun::latest());
    }

    public static function videos(WP_REST_Request $request)
    {
        $limit = min(max((int) $request->get_param('limit'), 1), 10);
        $videos = Jazireh_YouTube::cached_videos($limit);
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
        $author_id = (int) $post->post_author;
        $author_name = $author_id ? get_the_author_meta('display_name', $author_id) : '';
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
            'author' => $author_name ?: '',
            'translator' => get_post_meta($post->ID, Jazireh_News::META_TRANSLATOR, true) ?: '',
            'sourceName' => get_post_meta($post->ID, Jazireh_News::META_SOURCE_NAME, true) ?: '',
            'sourceUrl' => get_post_meta($post->ID, Jazireh_News::META_SOURCE_URL, true) ?: '',
            'publishedAt' => get_post_time(DATE_ATOM, true, $post),
            'updatedAt' => get_post_modified_time(DATE_ATOM, true, $post)
        );
    }

    private static function sky_payload($for_home = false)
    {
        return Jazireh_Astronomy::sky_payload($for_home);
    }

    private static function latest_apod_items($limit)
    {
        return Jazireh_APOD_Service::latest_items($limit);
    }

    private static function home_sun_payload()
    {
        $widget = Jazireh_Widgets::cached_or_last_good('sun', 'Fresh solar imagery is being refreshed; last known good result returned.');
        $data = is_array($widget) && !empty($widget['data']) && is_array($widget['data']) ? $widget['data'] : array();
        return array(
            'status' => is_array($widget) ? (string) ($widget['status'] ?? 'stale') : 'stale',
            'title' => (string) ($data['title'] ?? 'خورشید اکنون'),
            'image' => (string) ($data['image'] ?? ($data['fallbackImage'] ?? '')),
            'fallbackImage' => (string) ($data['fallbackImage'] ?? ''),
            'sourceName' => (string) ($data['source'] ?? ($widget['source'] ?? '')),
            'sourceUrl' => (string) ($data['sourceUrl'] ?? ($widget['sourceUrl'] ?? '')),
            'wavelength' => (string) ($data['wavelength'] ?? '304 Å'),
            'observedAt' => (string) ($data['observedAt'] ?? ''),
            'isFallback' => !is_array($widget) || (string) ($widget['status'] ?? '') !== Jazireh_Widgets::STATE_READY,
            'displayWarning' => (string) ($data['displayWarning'] ?? ($widget['message'] ?? '')),
        );
    }

    private static function home_sky_payload()
    {
        return Jazireh_Sky_Service::compatibility_payload(true);
    }

    private static function home_apod_payload()
    {
        return Jazireh_APOD_Service::cached_home_payload();
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
