<?php

if (!defined('ABSPATH')) {
    exit;
}

class Jazireh_Sun
{
    private const CACHE_KEY = 'jazireh_sun_now';
    private const LAST_GOOD_KEY = 'jazireh_sun_now_last_good';

    public static function latest()
    {
        return Jazireh_Sun_Service::compatibility_payload();
    }

    public static function legacy_latest()
    {
        $cached = get_transient(self::CACHE_KEY);
        if (is_array($cached)) {
            return self::with_metadata($cached);
        }

        $payload = self::helioviewer_payload();
        if (self::remote_image_is_available($payload['image'])) {
            set_transient(self::CACHE_KEY, $payload, 15 * MINUTE_IN_SECONDS);
            set_transient(self::LAST_GOOD_KEY, $payload, 2 * DAY_IN_SECONDS);
            return self::with_metadata($payload);
        }

        $fallback = self::sdo_payload();
        if (self::remote_image_is_available($fallback['image'])) {
            set_transient(self::CACHE_KEY, $fallback, 30 * MINUTE_IN_SECONDS);
            set_transient(self::LAST_GOOD_KEY, $fallback, 2 * DAY_IN_SECONDS);
            return self::with_metadata($fallback);
        }

        $last_good = get_transient(self::LAST_GOOD_KEY);
        if (is_array($last_good)) {
            $last_good['status'] = 'stale';
            $last_good['message'] = 'دریافت تصویر تازه ممکن نبود؛ آخرین تصویر سالم نمایش داده می‌شود.';
            $last_good['isFallback'] = true;
            $last_good['fallbackReason'] = 'دریافت تصویر تازه از Helioviewer یا SDO ممکن نبود.';
            $last_good['displayWarning'] = 'داده پشتیبان: آخرین تصویر سالم نمایش داده می‌شود.';
            set_transient(self::CACHE_KEY, $last_good, 10 * MINUTE_IN_SECONDS);
            return self::with_metadata($last_good);
        }

        $now = time();
        $fallback['status'] = 'stale';
        $fallback['message'] = 'تصویر پشتیبان SDO نمایش داده می‌شود.';
        $fallback['isFallback'] = true;
        $fallback['fallbackReason'] = 'دریافت تصویر تازه از Helioviewer یا SDO ممکن نبود.';
        $fallback['displayWarning'] = 'داده پشتیبان: تصویر پشتیبان SDO نمایش داده می‌شود.';
        $fallback['fetchedAt'] = wp_date(DATE_ATOM, $now);
        $fallback['calculatedAt'] = wp_date(DATE_ATOM, $now);
        $fallback['generatedAtUtc'] = gmdate(DATE_ATOM, $now);
        $fallback['timezone'] = wp_timezone_string();
        $fallback['location'] = self::location_meta();
        set_transient(self::CACHE_KEY, $fallback, 30 * MINUTE_IN_SECONDS);
        return self::with_metadata($fallback);
    }

    private static function helioviewer_payload()
    {
        $now = time();
        $date = gmdate('Y-m-d\TH:i:s\Z', $now - (15 * MINUTE_IN_SECONDS));
        $url = add_query_arg(array(
            'date' => $date,
            'imageScale' => '2.4204409',
            'layers' => '[SDO,AIA,AIA,304,1,100]',
            'events' => '',
            'eventLabels' => 'false',
            'x0' => '0',
            'y0' => '0',
            'width' => '1024',
            'height' => '1024',
            'display' => 'true',
            'watermark' => 'true',
        ), 'https://api.helioviewer.org/v2/takeScreenshot/');

        return array(
            'status' => 'ready',
            'title' => 'خورشید اکنون',
            'image' => esc_url_raw($url),
            'sourceName' => 'Helioviewer - SDO/AIA',
            'sourceUrl' => 'https://helioviewer.org/',
            'wavelength' => 'AIA 304Å',
            'observedAt' => $date,
            'fetchedAt' => wp_date(DATE_ATOM, $now),
            'calculatedAt' => wp_date(DATE_ATOM, $now),
            'generatedAtUtc' => gmdate(DATE_ATOM, $now),
            'timezone' => wp_timezone_string(),
            'location' => self::location_meta(),
            'source' => 'helioviewer-sdo-aia',
            'accuracy' => 'observed-image',
            'confidence' => 'high',
            'isFallback' => false,
            'fallbackReason' => '',
            'displayWarning' => '',
            'fallbackLevel' => 'helioviewer',
            'message' => 'تصویر زنده نزدیک به اکنون از داده‌های SDO/AIA نمایش داده می‌شود.',
        );
    }

    private static function sdo_payload()
    {
        $now = time();
        return array(
            'status' => 'stale',
            'title' => 'خورشید اکنون',
            'image' => 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_1024_0304.jpg',
            'sourceName' => 'NASA SDO',
            'sourceUrl' => 'https://sdo.gsfc.nasa.gov/data/',
            'wavelength' => 'AIA 304Å',
            'observedAt' => '',
            'fetchedAt' => wp_date(DATE_ATOM, $now),
            'calculatedAt' => wp_date(DATE_ATOM, $now),
            'generatedAtUtc' => gmdate(DATE_ATOM, $now),
            'timezone' => wp_timezone_string(),
            'location' => self::location_meta(),
            'source' => 'sdo-latest-image',
            'accuracy' => 'observed-image-stale',
            'confidence' => 'medium',
            'isFallback' => true,
            'fallbackReason' => 'Helioviewer پاسخ نداد؛ زمان واقعی ثبت تصویر SDO از این فایل تایید نشده است.',
            'displayWarning' => 'داده پشتیبان: زمان دقیق ثبت تصویر تایید نشده است.',
            'fallbackLevel' => 'sdo',
            'message' => 'Helioviewer پاسخ نداد؛ تصویر آماده SDO نمایش داده می‌شود.',
        );
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

    private static function with_metadata($payload)
    {
        $now = time();
        $status = (string) ($payload['status'] ?? 'stale');
        $fallback_level = (string) ($payload['fallbackLevel'] ?? '');
        $is_fallback = array_key_exists('isFallback', $payload) ? (bool) $payload['isFallback'] : ($status !== 'ready' || $fallback_level === 'sdo' || $fallback_level === 'frontend');
        $ephemeris = self::ephemeris_payload($now);
        $ephemeris_fallback = !empty($ephemeris['isFallback']);

        $defaults = array(
            'calculatedAt' => wp_date(DATE_ATOM, $now),
            'generatedAtUtc' => gmdate(DATE_ATOM, $now),
            'timezone' => wp_timezone_string(),
            'location' => self::location_meta(),
            'observer' => $ephemeris['observer'],
            'source' => ($fallback_level === 'helioviewer' ? 'helioviewer-sdo-aia' : 'helioviewer,sdo') . ',jpl-horizons',
            'accuracy' => $ephemeris_fallback ? 'observed-image-with-unavailable-ephemeris' : ($is_fallback ? 'observed-image-stale-authoritative-position' : 'observed-image-authoritative-position'),
            'confidence' => ($is_fallback || $ephemeris_fallback) ? 'medium' : 'high',
            'isFallback' => $is_fallback || $ephemeris_fallback,
            'fallbackReason' => $ephemeris_fallback ? $ephemeris['fallbackReason'] : ($is_fallback ? 'تصویر تازه یا زمان دقیق ثبت تصویر تایید نشده است.' : ''),
            'displayWarning' => $ephemeris_fallback ? $ephemeris['displayWarning'] : ($is_fallback ? 'داده پشتیبان: زمان دقیق ثبت تصویر تایید نشده است.' : ''),
            'altitude' => $ephemeris['altitude'],
            'azimuth' => $ephemeris['azimuth'],
            'rightAscension' => $ephemeris['rightAscension'],
            'declination' => $ephemeris['declination'],
            'distance' => $ephemeris['distance'],
            'apparentMagnitude' => $ephemeris['apparentMagnitude'],
            'transit' => $ephemeris['transit'],
            'ephemeris' => $ephemeris['ephemeris'],
        );

        return array_merge($payload, $defaults);
    }

    private static function ephemeris_payload($timestamp)
    {
        $location = self::location_meta();
        $observer = array(
            'city' => $location['city'],
            'latitude' => $location['lat'],
            'longitude' => $location['lng'],
            'timezone' => wp_timezone_string(),
        );
        $ephemeris = Jazireh_Ephemeris::object('sun', $observer, $timestamp, 'sun-now');
        $fallback = is_array($ephemeris['fallback'] ?? null) ? $ephemeris['fallback'] : array(
            'isFallback' => true,
            'fallbackReason' => 'sun ephemeris unavailable',
            'displayWarning' => 'داده معتبر موقعیت خورشید فعلا در دسترس نیست.',
        );
        $ready = !empty($ephemeris['status']) && $ephemeris['status'] !== 'unavailable' && empty($fallback['isFallback']);
        $position = is_array($ephemeris['position'] ?? null) ? $ephemeris['position'] : array();
        $appearance = is_array($ephemeris['appearance'] ?? null) ? $ephemeris['appearance'] : array();

        return array(
            'observer' => $ephemeris['observer'] ?? Jazireh_Ephemeris::normalize_observer($observer),
            'isFallback' => !$ready,
            'fallbackReason' => $ready ? '' : (string) ($fallback['fallbackReason'] ?? 'sun ephemeris unavailable'),
            'displayWarning' => $ready ? '' : 'داده معتبر موقعیت خورشید فعلا در دسترس نیست.',
            'altitude' => $ready ? ($position['altitude'] ?? null) : null,
            'azimuth' => $ready ? ($position['azimuth'] ?? null) : null,
            'rightAscension' => $ready ? ($position['rightAscension'] ?? null) : null,
            'declination' => $ready ? ($position['declination'] ?? null) : null,
            'distance' => $ready ? ($position['distanceAu'] ?? null) : null,
            'apparentMagnitude' => $ready ? ($appearance['magnitude'] ?? null) : null,
            'transit' => null,
            'ephemeris' => array(
                'status' => $ephemeris['status'] ?? 'unavailable',
                'source' => $ephemeris['source'] ?? 'jpl-horizons',
                'accuracy' => $ephemeris['accuracy'] ?? 'unavailable',
                'confidence' => $ephemeris['confidence'] ?? 'low',
                'calculatedAt' => $ephemeris['time']['calculatedAt'] ?? wp_date(DATE_ATOM, $timestamp),
                'generatedAtUtc' => $ephemeris['time']['generatedAtUtc'] ?? gmdate(DATE_ATOM, $timestamp),
                'observer' => $ephemeris['observer'] ?? Jazireh_Ephemeris::normalize_observer($observer),
                'isFallback' => !$ready,
                'fallbackReason' => $ready ? '' : (string) ($fallback['fallbackReason'] ?? 'sun ephemeris unavailable'),
                'displayWarning' => $ready ? '' : 'داده معتبر موقعیت خورشید فعلا در دسترس نیست.',
                'riseSetTransitStatus' => 'transit-not-calculated',
            ),
        );
    }

    private static function remote_image_is_available($url)
    {
        if (!$url) {
            return false;
        }

        $response = wp_remote_head($url, array(
            'timeout' => 8,
            'redirection' => 3,
        ));

        if (!is_wp_error($response)) {
            $status = (int) wp_remote_retrieve_response_code($response);
            if ($status >= 200 && $status < 400) {
                return true;
            }
        }

        $response = wp_remote_get($url, array(
            'timeout' => 8,
            'redirection' => 3,
            'limit_response_size' => 1024,
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        return $status >= 200 && $status < 400;
    }
}
