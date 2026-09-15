<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Sun_Service
{
    const WIDGET_KEY = 'sun';
    const CACHE_TTL = 1800;
    const HELIOVIEWER_API_BASE = 'https://api.helioviewer.org/v2';
    const HELIOVIEWER_SITE = 'https://helioviewer.org/';
    const SDO_SITE = 'https://sdo.gsfc.nasa.gov/data/';
    const SDO_LATEST_304 = 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_1024_0304.jpg';

    public static function widget($args = array())
    {
        $force_refresh = !empty($args['forceRefresh']);
        $cached = Jazireh_Widgets::get_cached(self::WIDGET_KEY);
        if (!$force_refresh && is_array($cached) && !Jazireh_Widgets::is_stale($cached)) {
            return $cached;
        }

        if (!$force_refresh) {
            $last_good = Jazireh_Widgets::last_good_as_stale(self::WIDGET_KEY, 'Fresh solar imagery is being refreshed; last known good result returned.');
            if (is_array($last_good)) {
                return $last_good;
            }
        }

        $payload = self::fetch_latest_payload();
        if (is_wp_error($payload)) {
            $payload = self::fallback_payload($payload->get_error_message());
            $response = Jazireh_Widgets::stale(self::WIDGET_KEY, $payload, array(
                'updatedAt' => current_time('timestamp', true),
                'expiresAt' => current_time('timestamp', true) + self::CACHE_TTL,
                'source' => $payload['source'],
                'sourceUrl' => $payload['sourceUrl'],
                'message' => $payload['message'],
            ));
            return Jazireh_Widgets::set_cached(self::WIDGET_KEY, $response, self::CACHE_TTL);
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

    public static function compatibility_payload()
    {
        $widget = self::widget();
        $data = is_array($widget) && !empty($widget['data']) && is_array($widget['data']) ? $widget['data'] : array();
        $status = isset($widget['status']) ? sanitize_key((string) $widget['status']) : 'error';
        $message = isset($widget['message']) ? (string) $widget['message'] : '';

        return array(
            'status' => $status,
            'title' => isset($data['title']) ? $data['title'] : 'خورشید اکنون',
            'image' => self::compatibility_image_url(isset($data['fallbackImage']) ? $data['fallbackImage'] : (isset($data['image']) ? $data['image'] : '')),
            'fallbackImage' => self::compatibility_image_url(isset($data['fallbackImage']) ? $data['fallbackImage'] : ''),
            'sourceName' => isset($data['source']) ? $data['source'] : (isset($widget['source']) ? $widget['source'] : ''),
            'sourceUrl' => isset($data['sourceUrl']) ? $data['sourceUrl'] : (isset($widget['sourceUrl']) ? $widget['sourceUrl'] : ''),
            'wavelength' => isset($data['wavelength']) ? $data['wavelength'] : 'AIA 304Å',
            'observedAt' => isset($data['observedAt']) ? $data['observedAt'] : '',
            'fetchedAt' => isset($widget['updatedAt']) ? $widget['updatedAt'] : '',
            'calculatedAt' => isset($widget['updatedAt']) ? $widget['updatedAt'] : '',
            'generatedAtUtc' => isset($widget['updatedAt']) ? $widget['updatedAt'] : '',
            'timezone' => wp_timezone_string(),
            'source' => isset($widget['source']) ? $widget['source'] : '',
            'accuracy' => $status === Jazireh_Widgets::STATE_READY ? 'observed-image' : 'unavailable',
            'confidence' => $status === Jazireh_Widgets::STATE_READY ? 'high' : 'low',
            'isFallback' => $status !== Jazireh_Widgets::STATE_READY || !empty($data['isFallback']),
            'fallbackReason' => isset($data['fallbackReason']) ? $data['fallbackReason'] : ($status === Jazireh_Widgets::STATE_READY ? '' : $message),
            'displayWarning' => isset($data['displayWarning']) ? $data['displayWarning'] : ($status === Jazireh_Widgets::STATE_READY ? '' : $message),
            'fallbackLevel' => $status === Jazireh_Widgets::STATE_READY ? 'helioviewer' : 'widget',
            'message' => $message ?: (isset($data['message']) ? $data['message'] : ''),
        );
    }

    private static function compatibility_image_url($url)
    {
        if (!$url || !is_string($url)) {
            return '';
        }
        return str_replace(array('+', '[', ']'), array('%2B', '%5B', '%5D'), trim($url));
    }

    private static function fetch_latest_payload()
    {
        $date = gmdate('Y-m-d\TH:i:s\Z', current_time('timestamp', true));
        $source = self::sources()[0];
        $metadata = self::closest_image($source, $date);
        if (is_wp_error($metadata)) {
            return $metadata;
        }

        $observed_at = self::observed_at($metadata);
        if (!$observed_at) {
            return new WP_Error('jazireh_sun_missing_timestamp', 'Helioviewer did not return an observation timestamp.', array('status' => 502));
        }

        return array(
            'image' => self::screenshot_url($source, $observed_at),
            'fallbackImage' => self::SDO_LATEST_304,
            'observedAt' => $observed_at,
            'wavelength' => $source['wavelength'],
            'source' => $source['label'],
            'sourceUrl' => self::HELIOVIEWER_SITE,
            'provider' => 'Helioviewer',
            'sourceId' => $source['sourceId'],
            'observatory' => $source['observatory'],
            'instrument' => $source['instrument'],
            'measurement' => $source['measurement'],
        );
    }

    private static function fallback_payload($reason)
    {
        $now = current_time('timestamp', true);
        return array(
            'status' => 'stale',
            'title' => 'خورشید اکنون',
            'image' => self::SDO_LATEST_304,
            'fallbackImage' => self::SDO_LATEST_304,
            'observedAt' => '',
            'wavelength' => 'AIA 304Å',
            'source' => 'NASA SDO',
            'sourceUrl' => self::SDO_SITE,
            'provider' => 'NASA SDO',
            'sourceId' => 'sdo-latest',
            'observatory' => 'SDO',
            'instrument' => 'AIA',
            'measurement' => '304',
            'message' => 'Helioviewer در دسترس نیست؛ تصویر پشتیبان SDO نمایش داده می‌شود.',
            'displayWarning' => 'داده پشتیبان: Helioviewer در دسترس نیست.',
            'isFallback' => true,
            'fallbackReason' => $reason,
            'calculatedAt' => wp_date(DATE_ATOM, $now),
            'generatedAtUtc' => gmdate(DATE_ATOM, $now),
            'timezone' => wp_timezone_string(),
            'location' => self::location_meta(),
        );
    }

    private static function closest_image($source, $date)
    {
        $url = add_query_arg(array(
            'date' => $date,
            'sourceId' => $source['sourceId'],
        ), self::HELIOVIEWER_API_BASE . '/getClosestImage/');

        $response = wp_remote_get($url, array('timeout' => 5));
        if (is_wp_error($response)) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status < 200 || $status >= 300 || !is_array($data)) {
            return new WP_Error('jazireh_sun_bad_response', 'Helioviewer returned an invalid solar image response.', array('status' => 502));
        }

        if (empty($data['id']) || empty($data['date'])) {
            return new WP_Error('jazireh_sun_missing_data', 'Helioviewer solar image metadata is incomplete.', array('status' => 502));
        }

        return $data;
    }

    private static function screenshot_url($source, $observed_at)
    {
        return add_query_arg(array(
            'date' => $observed_at,
            'imageScale' => '2.4',
            'layers' => $source['layer'],
            'events' => '',
            'eventLabels' => 'false',
            'scale' => 'false',
            'x0' => '0',
            'y0' => '0',
            'width' => '1024',
            'height' => '1024',
            'display' => 'true',
            'watermark' => 'true',
        ), self::HELIOVIEWER_API_BASE . '/takeScreenshot/');
    }

    private static function observed_at($metadata)
    {
        $timestamp = strtotime((string) $metadata['date']);
        return $timestamp ? gmdate(DATE_ATOM, $timestamp) : '';
    }

    private static function location_meta()
    {
        $settings = get_option(Jazireh_Settings::OPTION_NAME, array());
        $city = is_array($settings) && !empty($settings['default_city']) ? (string) $settings['default_city'] : 'تهران';
        return array(
            'city' => $city === 'تهران' ? 'تهران، ایران' : $city,
            'lat' => is_array($settings) && isset($settings['default_latitude']) ? (float) $settings['default_latitude'] : 35.6892,
            'lng' => is_array($settings) && isset($settings['default_longitude']) ? (float) $settings['default_longitude'] : 51.3890,
        );
    }

    private static function sources()
    {
        return array(
            array(
                'label' => 'Helioviewer / SDO AIA',
                'sourceId' => 13,
                'observatory' => 'SDO',
                'instrument' => 'AIA',
                'measurement' => '304',
                'wavelength' => '304 Å',
                'layer' => '[SDO,AIA,AIA,304,1,100]',
            ),
            array(
                'label' => 'Helioviewer / SDO AIA',
                'sourceId' => 11,
                'observatory' => 'SDO',
                'instrument' => 'AIA',
                'measurement' => '193',
                'wavelength' => '193 Å',
                'layer' => '[SDO,AIA,AIA,193,1,100]',
            ),
            array(
                'label' => 'Helioviewer / SOHO EIT',
                'sourceId' => 3,
                'observatory' => 'SOHO',
                'instrument' => 'EIT',
                'measurement' => '304',
                'wavelength' => '304 Å',
                'layer' => '[SOHO,EIT,EIT,304,1,100]',
            ),
        );
    }
}
