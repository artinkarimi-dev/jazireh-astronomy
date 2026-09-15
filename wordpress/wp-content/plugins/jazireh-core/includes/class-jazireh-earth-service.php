<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Earth_Service
{
    const WIDGET_KEY = 'earth';
    const CACHE_TTL = 21600;
    const EPIC_API_BASE = 'https://api.nasa.gov/EPIC/api/natural';
    const EPIC_PUBLIC_API_BASE = 'https://epic.gsfc.nasa.gov/api/natural';
    const EPIC_ARCHIVE_BASE = 'https://epic.gsfc.nasa.gov/archive/natural';
    const EPIC_SITE = 'https://epic.gsfc.nasa.gov/';

    public static function widget($args = array())
    {
        $force_refresh = !empty($args['forceRefresh']);
        $cached = Jazireh_Widgets::get_cached(self::WIDGET_KEY);
        if (!$force_refresh && is_array($cached) && !Jazireh_Widgets::is_stale($cached)) {
            return $cached;
        }

        if (!$force_refresh) {
            $last_good = Jazireh_Widgets::last_good_as_stale(self::WIDGET_KEY, 'Fresh NASA EPIC imagery is being refreshed; last known good result returned.');
            if (is_array($last_good)) {
                return $last_good;
            }
        }

        $payload = self::fetch_latest_payload();
        if (is_wp_error($payload)) {
            return Jazireh_Widgets::error(self::WIDGET_KEY, $payload->get_error_message(), array(
                'source' => 'NASA EPIC',
                'sourceUrl' => self::EPIC_SITE,
            ));
        }

        $now = current_time('timestamp', true);
        $response = Jazireh_Widgets::ready(self::WIDGET_KEY, $payload, array(
            'updatedAt' => $now,
            'expiresAt' => $now + self::CACHE_TTL,
            'source' => $payload['source'],
            'sourceUrl' => $payload['sourceUrl'],
        ));

        return Jazireh_Widgets::set_cached(self::WIDGET_KEY, $response, self::CACHE_TTL);
    }

    public static function refresh()
    {
        Jazireh_Widgets::delete_cached(self::WIDGET_KEY);
        return self::widget(array('forceRefresh' => true));
    }

    private static function fetch_latest_payload()
    {
        $items = self::latest_images();
        if (is_wp_error($items)) {
            return $items;
        }

        if (empty($items[0]) || !is_array($items[0])) {
            return new WP_Error('jazireh_earth_empty_epic_response', 'NASA EPIC did not return Earth imagery.', array('status' => 502));
        }

        return self::format_item($items[0]);
    }

    private static function latest_images()
    {
        $last_error = null;
        foreach (self::metadata_sources() as $source) {
            $url = $source['url'];
            if (!empty($source['apiKey'])) {
                $url = add_query_arg(array('api_key' => self::nasa_api_key()), $url);
            }

            $response = wp_remote_get($url, array('timeout' => 12));
            if (is_wp_error($response)) {
                $last_error = new WP_Error('jazireh_earth_request_failed', $response->get_error_message(), array('status' => 502));
                continue;
            }

            $status = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!empty($data['error']['message'])) {
                $last_error = new WP_Error('jazireh_earth_api_error', sanitize_text_field($data['error']['message']), array('status' => 502));
                continue;
            }

            if ($status >= 200 && $status < 300 && is_array($data)) {
                return $data;
            }

            $last_error = new WP_Error('jazireh_earth_bad_response', 'NASA EPIC returned an invalid Earth imagery response.', array('status' => 502));
        }

        return $last_error ?: new WP_Error('jazireh_earth_unavailable', 'NASA EPIC imagery is unavailable.', array('status' => 502));
    }

    private static function metadata_sources()
    {
        return array(
            array(
                'url' => self::EPIC_API_BASE . '/images',
                'apiKey' => true,
            ),
            array(
                'url' => self::EPIC_PUBLIC_API_BASE,
                'apiKey' => false,
            ),
        );
    }

    private static function format_item($item)
    {
        $image_id = sanitize_file_name((string) ($item['image'] ?? ''));
        $date = sanitize_text_field((string) ($item['date'] ?? ''));
        $timestamp = self::timestamp($date);

        if ($image_id === '' || !$timestamp) {
            return new WP_Error('jazireh_earth_missing_data', 'NASA EPIC imagery metadata is incomplete.', array('status' => 502));
        }

        $caption = self::clean_text((string) ($item['caption'] ?? ''));
        $coords = is_array($item['centroid_coordinates'] ?? null) ? $item['centroid_coordinates'] : array();

        return array(
            'image' => self::image_url($image_id, $timestamp),
            'title' => 'Earth From Space',
            'caption' => $caption ?: 'Natural-color Earth imagery from NASA EPIC on DSCOVR.',
            'timestamp' => gmdate(DATE_ATOM, $timestamp),
            'source' => 'NASA EPIC',
            'sourceUrl' => self::EPIC_SITE,
            'provider' => 'NASA EPIC / DSCOVR',
            'imageId' => $image_id,
            'location' => array(
                'label' => 'Earth disk center',
                'latitude' => isset($coords['lat']) ? (float) $coords['lat'] : null,
                'longitude' => isset($coords['lon']) ? (float) $coords['lon'] : null,
            ),
            'metadata' => array(
                'version' => sanitize_text_field((string) ($item['version'] ?? '')),
                'centroidCoordinates' => array(
                    'lat' => isset($coords['lat']) ? (float) $coords['lat'] : null,
                    'lon' => isset($coords['lon']) ? (float) $coords['lon'] : null,
                ),
            ),
        );
    }

    private static function image_url($image_id, $timestamp)
    {
        return sprintf(
            '%s/%s/png/%s.png',
            self::EPIC_ARCHIVE_BASE,
            gmdate('Y/m/d', $timestamp),
            rawurlencode($image_id)
        );
    }

    private static function timestamp($date)
    {
        $timestamp = strtotime((string) $date . ' UTC');
        return $timestamp ? $timestamp : 0;
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

    private static function clean_text($text)
    {
        return html_entity_decode(wp_strip_all_tags((string) $text), ENT_QUOTES, 'UTF-8');
    }
}
