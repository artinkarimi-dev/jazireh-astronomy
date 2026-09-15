<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_YouTube
{
    const CACHE_PREFIX = 'jazireh_youtube_latest_videos_';
    const LAST_GOOD_PREFIX = 'jazireh_youtube_latest_videos_last_good_';
    const META_CACHE_KEY = 'jazireh_youtube_channel_meta';
    const STATUS_OPTION = 'jazireh_youtube_status';
    const CHANNEL_META_TTL = DAY_IN_SECONDS;
    const VIDEOS_TTL = 45 * MINUTE_IN_SECONDS;
    const FETCH_CANDIDATES = 12;
    const DEFAULT_HANDLE = '@Jazireh';

    public static function boot()
    {
        add_action('admin_post_jazireh_refresh_videos', array(__CLASS__, 'handle_refresh'));
    }

    public static function latest_videos($limit = 3, $force_refresh = false)
    {
        $limit = min(max((int) $limit, 1), 10);
        $cache_key = self::CACHE_PREFIX . $limit;
        $last_good_key = self::LAST_GOOD_PREFIX . $limit;

        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if (is_array($cached)) {
                self::touch_status(array(
                    'cache_state' => 'fresh',
                ));
                return $cached;
            }

            $last_good = get_transient($last_good_key);
            if (is_array($last_good)) {
                self::touch_status(array(
                    'cache_state' => 'last-known-good',
                ));
                return $last_good;
            }
        }

        self::touch_status(array(
            'last_attempt' => current_time('mysql', 1),
            'cache_state' => 'refreshing',
        ));

        $api_key = self::api_key();
        if (!$api_key) {
            return self::fallback_or_error(
                $limit,
                new WP_Error('jazireh_youtube_missing_api_key', 'YouTube API key is not configured.', array('status' => 503))
            );
        }

        $channel_meta = self::channel_meta($api_key);
        if (is_wp_error($channel_meta)) {
            return self::fallback_or_error($limit, $channel_meta);
        }

        $video_ids = self::latest_candidate_video_ids($api_key, $channel_meta['uploadsPlaylistId'], $limit);
        if (is_wp_error($video_ids)) {
            return self::fallback_or_error($limit, $video_ids, $channel_meta);
        }

        if (empty($video_ids)) {
            $videos = array();
        } else {
            $videos = self::video_details($api_key, $video_ids, $limit);
            if (is_wp_error($videos)) {
                return self::fallback_or_error($limit, $videos, $channel_meta);
            }
        }

        set_transient($cache_key, $videos, self::VIDEOS_TTL);
        set_transient($last_good_key, $videos, 7 * DAY_IN_SECONDS);

        self::touch_status(array(
            'last_success' => current_time('mysql', 1),
            'last_error' => '',
            'cache_state' => 'fresh',
            'channel_id' => $channel_meta['channelId'],
            'uploads_playlist_id' => $channel_meta['uploadsPlaylistId'],
            'videos_count' => count($videos),
        ));

        return $videos;
    }

    public static function cached_videos($limit = 3)
    {
        $limit = min(max((int) $limit, 1), 10);
        $candidate_limits = array_values(array_unique(array($limit, 10, 6, 3)));
        $candidate_keys = array();
        foreach ($candidate_limits as $candidate_limit) {
            $candidate_keys[] = self::CACHE_PREFIX . $candidate_limit;
            $candidate_keys[] = self::LAST_GOOD_PREFIX . $candidate_limit;
        }

        foreach ($candidate_keys as $key) {
            $cached = get_transient($key);
            if (is_array($cached)) {
                return array_slice($cached, 0, $limit);
            }
        }
        return array();
    }

    public static function prewarm()
    {
        $videos = self::latest_videos(3, true);
        if (is_wp_error($videos)) {
            return array(
                'status' => Jazireh_Widgets::STATE_ERROR,
                'message' => $videos->get_error_message(),
                'data' => array(),
            );
        }

        return array(
            'status' => Jazireh_Widgets::STATE_READY,
            'message' => '',
            'data' => $videos,
        );
    }

    public static function diagnostics()
    {
        $status = get_option(self::STATUS_OPTION, array());
        $status = is_array($status) ? $status : array();

        $meta = get_transient(self::META_CACHE_KEY);
        if (is_array($meta)) {
            $status['channel_id'] = $status['channel_id'] ?? ($meta['channelId'] ?? '');
            $status['uploads_playlist_id'] = $status['uploads_playlist_id'] ?? ($meta['uploadsPlaylistId'] ?? '');
        }

        return array(
            'last_success' => (string) ($status['last_success'] ?? ''),
            'last_attempt' => (string) ($status['last_attempt'] ?? ''),
            'last_error' => (string) ($status['last_error'] ?? ''),
            'cache_state' => (string) ($status['cache_state'] ?? self::cache_state(3)),
            'channel_id' => (string) ($status['channel_id'] ?? ''),
            'uploads_playlist_id' => (string) ($status['uploads_playlist_id'] ?? ''),
            'videos_count' => (int) ($status['videos_count'] ?? 0),
        );
    }

    public static function integrations_panel()
    {
        $diagnostics = self::diagnostics();
        $refresh_url = wp_nonce_url(
            admin_url('admin-post.php?action=jazireh_refresh_videos'),
            'jazireh_refresh_videos'
        );
        ?>
        <div class="jazireh-settings-card">
            <h2>YouTube Videos</h2>
            <p>The public site reads latest videos from WordPress cache. YouTube Data API requests run server-side only and fall back to last-known-good results if upstream requests fail.</p>
            <div class="jazireh-settings-grid two-col">
                <div class="jazireh-field"><label>Last successful fetch</label><input type="text" readonly class="regular-text" value="<?php echo esc_attr($diagnostics['last_success']); ?>"></div>
                <div class="jazireh-field"><label>Last attempt</label><input type="text" readonly class="regular-text" value="<?php echo esc_attr($diagnostics['last_attempt']); ?>"></div>
                <div class="jazireh-field"><label>Current cache state</label><input type="text" readonly class="regular-text" value="<?php echo esc_attr($diagnostics['cache_state']); ?>"></div>
                <div class="jazireh-field"><label>Latest items cached</label><input type="text" readonly class="regular-text" value="<?php echo esc_attr((string) $diagnostics['videos_count']); ?>"></div>
                <div class="jazireh-field"><label>Channel ID</label><input type="text" readonly class="regular-text code" value="<?php echo esc_attr($diagnostics['channel_id']); ?>"></div>
                <div class="jazireh-field"><label>Uploads playlist</label><input type="text" readonly class="regular-text code" value="<?php echo esc_attr($diagnostics['uploads_playlist_id']); ?>"></div>
                <div class="jazireh-field" style="grid-column:1 / -1"><label>Last error</label><textarea rows="3" readonly><?php echo esc_textarea($diagnostics['last_error']); ?></textarea></div>
            </div>
            <?php if (current_user_can('manage_options')) : ?>
                <p><a class="button button-secondary" href="<?php echo esc_url($refresh_url); ?>">Refresh / Sync Videos</a></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function handle_refresh()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden', 403);
        }
        check_admin_referer('jazireh_refresh_videos');

        $result = self::latest_videos(3, true);
        $redirect = add_query_arg(array(
            'page' => 'jazireh-settings',
            'tab' => 'integrations',
            'videos_refresh' => is_wp_error($result) ? 'error' : 'success',
            'videos_message' => rawurlencode(is_wp_error($result) ? $result->get_error_message() : 'Video cache refreshed.'),
        ), admin_url('admin.php'));

        wp_safe_redirect($redirect);
        exit;
    }

    private static function api_key()
    {
        if (defined('JAZIREH_YOUTUBE_API_KEY') && JAZIREH_YOUTUBE_API_KEY) {
            return JAZIREH_YOUTUBE_API_KEY;
        }
        $env_key = getenv('JAZIREH_YOUTUBE_API_KEY');
        return is_string($env_key) ? $env_key : '';
    }

    private static function channel_meta($api_key)
    {
        if (defined('JAZIREH_YOUTUBE_CHANNEL_ID') && JAZIREH_YOUTUBE_CHANNEL_ID) {
            $channel_id = sanitize_text_field(JAZIREH_YOUTUBE_CHANNEL_ID);
            $cached = get_transient(self::META_CACHE_KEY);
            if (is_array($cached) && ($cached['channelId'] ?? '') === $channel_id && !empty($cached['uploadsPlaylistId'])) {
                return $cached;
            }
            return self::channel_meta_from_channel_id($api_key, $channel_id);
        }

        $cached = get_transient(self::META_CACHE_KEY);
        if (is_array($cached) && !empty($cached['channelId']) && !empty($cached['uploadsPlaylistId'])) {
            return $cached;
        }

        return self::channel_meta_from_handle($api_key, self::configured_handle());
    }

    private static function channel_meta_from_handle($api_key, $handle)
    {
        $url = add_query_arg(array(
            'part' => 'id,contentDetails,snippet',
            'forHandle' => ltrim($handle, '@'),
            'maxResults' => 1,
            'key' => $api_key,
        ), 'https://www.googleapis.com/youtube/v3/channels');

        $data = self::remote_json($url);
        if (is_wp_error($data)) {
            return $data;
        }

        return self::normalize_channel_meta($data);
    }

    private static function channel_meta_from_channel_id($api_key, $channel_id)
    {
        $url = add_query_arg(array(
            'part' => 'id,contentDetails,snippet',
            'id' => $channel_id,
            'maxResults' => 1,
            'key' => $api_key,
        ), 'https://www.googleapis.com/youtube/v3/channels');

        $data = self::remote_json($url);
        if (is_wp_error($data)) {
            return $data;
        }

        return self::normalize_channel_meta($data);
    }

    private static function normalize_channel_meta($data)
    {
        $item = !empty($data['items'][0]) && is_array($data['items'][0]) ? $data['items'][0] : null;
        if (!$item) {
            return new WP_Error('jazireh_youtube_channel_not_found', 'YouTube channel was not found.', array('status' => 502));
        }

        $channel_id = sanitize_text_field((string) ($item['id'] ?? ''));
        $uploads_playlist_id = sanitize_text_field((string) ($item['contentDetails']['relatedPlaylists']['uploads'] ?? ''));
        if ($channel_id === '' || $uploads_playlist_id === '') {
            return new WP_Error('jazireh_youtube_channel_invalid', 'YouTube channel metadata is incomplete.', array('status' => 502));
        }

        $meta = array(
            'channelId' => $channel_id,
            'uploadsPlaylistId' => $uploads_playlist_id,
            'channelTitle' => sanitize_text_field((string) ($item['snippet']['title'] ?? 'Jazireh')),
            'resolvedAt' => current_time('mysql', 1),
        );
        set_transient(self::META_CACHE_KEY, $meta, self::CHANNEL_META_TTL);

        self::touch_status(array(
            'channel_id' => $channel_id,
            'uploads_playlist_id' => $uploads_playlist_id,
        ));

        return $meta;
    }

    private static function configured_handle()
    {
        $settings = Jazireh_Settings::get_settings();
        $handle = (string) ($settings['social']['youtube_handle'] ?? '');
        if ($handle !== '') {
            return $handle[0] === '@' ? $handle : '@' . $handle;
        }
        if (defined('JAZIREH_YOUTUBE_HANDLE') && JAZIREH_YOUTUBE_HANDLE) {
            $constant = (string) JAZIREH_YOUTUBE_HANDLE;
            return $constant[0] === '@' ? $constant : '@' . $constant;
        }
        return self::DEFAULT_HANDLE;
    }

    private static function latest_candidate_video_ids($api_key, $uploads_playlist_id, $limit)
    {
        $max_results = max($limit + 6, self::FETCH_CANDIDATES);
        $url = add_query_arg(array(
            'part' => 'snippet,contentDetails,status',
            'playlistId' => $uploads_playlist_id,
            'maxResults' => min($max_results, 25),
            'key' => $api_key,
        ), 'https://www.googleapis.com/youtube/v3/playlistItems');

        $data = self::remote_json($url);
        if (is_wp_error($data)) {
            return $data;
        }

        $ids = array();
        foreach ((array) ($data['items'] ?? array()) as $item) {
            $video_id = sanitize_text_field((string) ($item['contentDetails']['videoId'] ?? ''));
            if ($video_id === '') {
                continue;
            }
            $privacy = sanitize_text_field((string) ($item['status']['privacyStatus'] ?? 'public'));
            if ($privacy && $privacy !== 'public') {
                continue;
            }
            $ids[] = $video_id;
        }

        return array_values(array_unique($ids));
    }

    private static function video_details($api_key, array $video_ids, $limit)
    {
        $url = add_query_arg(array(
            'part' => 'snippet,contentDetails,liveStreamingDetails,status',
            'id' => implode(',', array_map('rawurlencode', $video_ids)),
            'key' => $api_key,
        ), 'https://www.googleapis.com/youtube/v3/videos');

        $data = self::remote_json($url);
        if (is_wp_error($data)) {
            return $data;
        }

        $items_by_id = array();
        foreach ((array) ($data['items'] ?? array()) as $item) {
            if (!empty($item['id'])) {
                $items_by_id[sanitize_text_field((string) $item['id'])] = $item;
            }
        }

        $videos = array();
        foreach ($video_ids as $video_id) {
            if (empty($items_by_id[$video_id])) {
                continue;
            }

            $normalized = self::normalize_video($items_by_id[$video_id]);
            if (!$normalized) {
                continue;
            }

            $videos[] = $normalized;
            if (count($videos) >= $limit) {
                break;
            }
        }

        return $videos;
    }

    private static function normalize_video($item)
    {
        $video_id = sanitize_text_field((string) ($item['id'] ?? ''));
        if ($video_id === '') {
            return null;
        }

        $status = (array) ($item['status'] ?? array());
        if (($status['privacyStatus'] ?? 'public') !== 'public') {
            return null;
        }

        $snippet = (array) ($item['snippet'] ?? array());
        $content_details = (array) ($item['contentDetails'] ?? array());
        $live_details = (array) ($item['liveStreamingDetails'] ?? array());

        $duration_iso = (string) ($content_details['duration'] ?? '');
        if (self::is_excluded_video($snippet, $content_details, $live_details, $duration_iso)) {
            return null;
        }

        $thumbnails = (array) ($snippet['thumbnails'] ?? array());
        $thumbnail = self::thumbnail_url($thumbnails);
        $youtube_url = 'https://www.youtube.com/watch?v=' . rawurlencode($video_id);

        return array(
            'id' => $video_id,
            'title' => html_entity_decode(wp_strip_all_tags((string) ($snippet['title'] ?? '')), ENT_QUOTES, 'UTF-8'),
            'description' => wp_strip_all_tags((string) ($snippet['description'] ?? '')),
            'youtubeUrl' => $youtube_url,
            'embedUrl' => 'https://www.youtube.com/embed/' . rawurlencode($video_id),
            'poster' => esc_url_raw($thumbnail),
            'duration' => self::format_duration($duration_iso),
            'publishedAt' => sanitize_text_field((string) ($snippet['publishedAt'] ?? '')),
            'channelTitle' => sanitize_text_field((string) ($snippet['channelTitle'] ?? 'Jazireh')),
            'type' => 'youtube',
        );
    }

    private static function is_excluded_video($snippet, $content_details, $live_details, $duration_iso)
    {
        $live_broadcast = sanitize_text_field((string) ($snippet['liveBroadcastContent'] ?? 'none'));
        if ($live_broadcast === 'live' || $live_broadcast === 'upcoming') {
            return true;
        }

        if (!empty($live_details['scheduledStartTime']) && empty($live_details['actualStartTime'])) {
            return true;
        }

        if (!empty($live_details['actualStartTime']) && empty($live_details['actualEndTime'])) {
            return true;
        }

        $dimension = sanitize_text_field((string) ($content_details['dimension'] ?? ''));
        if ($dimension === 'vertical') {
            return true;
        }

        if (self::iso_duration_seconds($duration_iso) > 0 && self::iso_duration_seconds($duration_iso) <= 90) {
            return true;
        }

        return false;
    }

    private static function thumbnail_url($thumbnails)
    {
        $order = array('maxres', 'standard', 'high', 'medium', 'default');
        foreach ($order as $key) {
            if (!empty($thumbnails[$key]['url'])) {
                return (string) $thumbnails[$key]['url'];
            }
        }
        return '';
    }

    private static function remote_json($url)
    {
        $response = wp_remote_get($url, array('timeout' => 12));
        if (is_wp_error($response)) {
            return new WP_Error('jazireh_youtube_request_failed', $response->get_error_message(), array('status' => 502));
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (!empty($data['error']['message'])) {
            return new WP_Error('jazireh_youtube_api_error', sanitize_text_field($data['error']['message']), array('status' => 502));
        }
        if ($status < 200 || $status >= 300 || !is_array($data)) {
            return new WP_Error('jazireh_youtube_bad_response', 'YouTube API returned an invalid response.', array('status' => 502));
        }

        return $data;
    }

    private static function fallback_or_error($limit, WP_Error $error, $channel_meta = array())
    {
        $last_good = get_transient(self::LAST_GOOD_PREFIX . $limit);
        self::touch_status(array(
            'last_error' => $error->get_error_message(),
            'cache_state' => is_array($last_good) ? 'last-known-good' : 'empty',
            'channel_id' => is_array($channel_meta) ? (string) ($channel_meta['channelId'] ?? '') : '',
            'uploads_playlist_id' => is_array($channel_meta) ? (string) ($channel_meta['uploadsPlaylistId'] ?? '') : '',
        ));

        if (is_array($last_good)) {
            return $last_good;
        }

        return $error;
    }

    private static function cache_state($limit)
    {
        if (is_array(get_transient(self::CACHE_PREFIX . $limit))) {
            return 'fresh';
        }
        if (is_array(get_transient(self::LAST_GOOD_PREFIX . $limit))) {
            return 'last-known-good';
        }
        return 'empty';
    }

    private static function touch_status($changes)
    {
        $status = get_option(self::STATUS_OPTION, array());
        $status = is_array($status) ? $status : array();
        update_option(self::STATUS_OPTION, array_merge($status, $changes), false);
    }

    private static function format_duration($duration)
    {
        if (!is_string($duration) || $duration === '') {
            return '';
        }
        if (!preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/', $duration, $matches)) {
            return '';
        }

        $hours = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 0;
        $minutes = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 0;
        $seconds = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : 0;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    private static function iso_duration_seconds($duration)
    {
        if (!is_string($duration) || $duration === '') {
            return 0;
        }
        if (!preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/', $duration, $matches)) {
            return 0;
        }

        $hours = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 0;
        $minutes = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 0;
        $seconds = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : 0;

        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }
}
