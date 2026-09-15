<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Earthquake_Service
{
    const WIDGET_KEY = 'earthquakes';
    const CACHE_TTL = 900;
    const USGS_FEED_URL = 'https://earthquake.usgs.gov/earthquakes/feed/v1.0/summary/all_day.geojson';
    const USGS_SOURCE_URL = 'https://earthquake.usgs.gov/earthquakes/feed/v1.0/geojson.php';
    const MAX_ITEMS = 12;

    public static function widget($args = array())
    {
        $force_refresh = !empty($args['forceRefresh']);
        $cached = Jazireh_Widgets::get_cached(self::WIDGET_KEY);
        if (!$force_refresh && is_array($cached) && !Jazireh_Widgets::is_stale($cached)) {
            return $cached;
        }

        if (!$force_refresh) {
            $last_good = Jazireh_Widgets::last_good_as_stale(self::WIDGET_KEY, 'Fresh USGS earthquake data is being refreshed; last known good result returned.');
            if (is_array($last_good)) {
                return $last_good;
            }
        }

        $payload = self::fetch_latest_payload();
        if (is_wp_error($payload)) {
            return Jazireh_Widgets::error(self::WIDGET_KEY, $payload->get_error_message(), array(
                'source' => 'USGS Earthquake Hazards Program',
                'sourceUrl' => self::USGS_SOURCE_URL,
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
        $response = wp_remote_get(self::USGS_FEED_URL, array(
            'timeout' => 12,
            'redirection' => 3,
        ));

        if (is_wp_error($response)) {
            return new WP_Error('jazireh_earthquakes_request_failed', $response->get_error_message(), array('status' => 502));
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status < 200 || $status >= 300 || !is_array($data) || !isset($data['features']) || !is_array($data['features'])) {
            return new WP_Error('jazireh_earthquakes_bad_response', 'USGS returned an invalid earthquake feed response.', array('status' => 502));
        }

        $earthquakes = array();
        foreach ($data['features'] as $feature) {
            $item = self::format_feature($feature);
            if (!$item) {
                continue;
            }
            $earthquakes[] = $item;
            if (count($earthquakes) >= self::MAX_ITEMS) {
                break;
            }
        }

        return array(
            'earthquakes' => $earthquakes,
            'count' => count($earthquakes),
            'feedTitle' => sanitize_text_field((string) ($data['metadata']['title'] ?? 'USGS earthquakes, past day')),
            'generatedAt' => self::format_time((int) ($data['metadata']['generated'] ?? 0)),
            'source' => 'USGS Earthquake Hazards Program',
            'sourceUrl' => self::USGS_SOURCE_URL,
        );
    }

    private static function format_feature($feature)
    {
        if (!is_array($feature)) {
            return null;
        }

        $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : array();
        $geometry = is_array($feature['geometry'] ?? null) ? $feature['geometry'] : array();
        $coordinates = is_array($geometry['coordinates'] ?? null) ? $geometry['coordinates'] : array();

        if (!isset($properties['time']) || count($coordinates) < 2) {
            return null;
        }

        return array(
            'id' => sanitize_text_field((string) ($feature['id'] ?? '')),
            'magnitude' => isset($properties['mag']) ? (float) $properties['mag'] : null,
            'place' => sanitize_text_field((string) ($properties['place'] ?? '')),
            'time' => self::format_time((int) $properties['time']),
            'depth' => isset($coordinates[2]) ? (float) $coordinates[2] : null,
            'coordinates' => array(
                'longitude' => (float) $coordinates[0],
                'latitude' => (float) $coordinates[1],
            ),
            'source' => sanitize_text_field((string) ($properties['net'] ?? 'USGS')),
            'detailUrl' => esc_url_raw((string) ($properties['url'] ?? '')),
        );
    }

    private static function format_time($milliseconds)
    {
        $seconds = $milliseconds > 100000000000 ? (int) floor($milliseconds / 1000) : (int) $milliseconds;
        return $seconds > 0 ? gmdate(DATE_ATOM, $seconds) : '';
    }
}
