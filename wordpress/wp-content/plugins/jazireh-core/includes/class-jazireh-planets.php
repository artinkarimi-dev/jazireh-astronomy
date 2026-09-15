<?php

if (!defined('ABSPATH')) {
    exit;
}

class Jazireh_Planets
{
    private const LAST_GOOD_CACHE_KEY = 'jazireh_planets_payload_last_good';
    private const LAST_GOOD_TTL = 2 * DAY_IN_SECONDS;

    private const PLANETS = array(
        'mercury' => array('name' => 'عطارد', 'color' => '#b7aca1'),
        'venus' => array('name' => 'زهره', 'color' => '#f2c572'),
        'mars' => array('name' => 'مریخ', 'color' => '#ef6b4a'),
        'jupiter' => array('name' => 'مشتری', 'color' => '#f0b879'),
        'saturn' => array('name' => 'زحل', 'color' => '#dcc17b'),
    );

    public static function payload($timestamp = null, $context = array())
    {
        $timestamp = $timestamp ?: current_time('timestamp', true);
        $settings = get_option(Jazireh_Settings::OPTION_NAME, array());
        $location = self::location($settings);
        $cache_key = self::payload_cache_key($timestamp, $location, $context);
        $force_refresh = !empty($context['forceRefresh']);
        $cached = get_transient($cache_key);
        if (!$force_refresh && is_array($cached)) {
            return $cached;
        }

        $observer = self::observer($location);
        $sun = Jazireh_Ephemeris::object('sun', $observer, $timestamp, 'planet-visibility-sun');
        $items = array();

        foreach (self::PLANETS as $id => $planet) {
            $ephemeris = Jazireh_Ephemeris::object($id, $observer, $timestamp, 'planet-current');
            $items[] = self::planet_visibility($id, $planet, $ephemeris, $sun, $context);
        }

        usort($items, array(__CLASS__, 'sort_by_visibility'));

        $has_fallback = count(array_filter($items, static function ($item) {
            return !empty($item['isFallback']);
        })) > 0;
        $has_ready = count(array_filter($items, static function ($item) {
            return $item['visibilityScore'] !== null;
        })) > 0;

        $payload = array(
            'status' => $has_ready ? 'ready' : 'unavailable',
            'accuracy' => $has_ready ? 'authoritative' : 'unavailable',
            'confidence' => $has_ready ? 'high' : 'low',
            'source' => 'jpl-horizons',
            'date' => wp_date('Y-m-d', $timestamp),
            'location' => $location,
            'observer' => $observer,
            'timezone' => wp_timezone_string(),
            'calculatedAt' => wp_date(DATE_ATOM, $timestamp),
            'generatedAtUtc' => gmdate(DATE_ATOM, $timestamp),
            'isFallback' => !$has_ready || $has_fallback,
            'fallbackReason' => $has_ready ? ($has_fallback ? 'some planet ephemeris values unavailable' : '') : 'planet ephemeris unavailable',
            'displayWarning' => $has_ready ? 'دیدپذیری از داده واقعی افمریس محاسبه شده است.' : 'داده معتبر دیدپذیری سیاره‌ها فعلا در دسترس نیست.',
            'futureFields' => array('altitude', 'azimuth', 'rightAscension', 'declination', 'distance', 'magnitude', 'rise', 'set', 'transit'),
            'items' => $items,
            'message' => $has_ready ? 'دیدپذیری سیاره‌ها از افمریس JPL Horizons و موقعیت رصدگر محاسبه می‌شود.' : 'داده JPL Horizons در دسترس نیست؛ مقدار ساختگی نمایش داده نمی‌شود.',
            'generatedAt' => wp_date(DATE_ATOM, $timestamp),
        );

        set_transient($cache_key, $payload, self::payload_cache_ttl($payload));
        if ($has_ready) {
            set_transient(self::LAST_GOOD_CACHE_KEY, $payload, self::LAST_GOOD_TTL);
        } else {
            $last_good = self::last_good_payload();
            if (is_array($last_good)) {
                return $last_good;
            }
        }

        return $payload;
    }

    public static function prewarm()
    {
        return self::payload(null, array('forceRefresh' => true));
    }

    private static function last_good_payload()
    {
        $cached = get_transient(self::LAST_GOOD_CACHE_KEY);
        if (!is_array($cached)) {
            return null;
        }

        $cached['status'] = 'stale';
        $cached['accuracy'] = 'stale';
        $cached['confidence'] = 'low';
        $cached['source'] = !empty($cached['source']) ? $cached['source'] . '-last-good' : 'last-good';
        $cached['isFallback'] = true;
        $cached['fallbackReason'] = 'fresh planet ephemeris unavailable; last valid result returned';
        $cached['displayWarning'] = 'آخرین داده معتبر دیدپذیری سیاره‌ها نمایش داده می‌شود.';
        $cached['message'] = 'داده تازه JPL Horizons در این لحظه آماده نبود؛ آخرین نتیجه معتبر با برچسب شفاف نمایش داده می‌شود.';
        $cached['servedAtUtc'] = gmdate(DATE_ATOM);

        if (!empty($cached['items']) && is_array($cached['items'])) {
            foreach ($cached['items'] as &$item) {
                if (is_array($item)) {
                    $item['status'] = !empty($item['status']) && $item['status'] !== 'unavailable' ? $item['status'] : 'stale';
                    $item['confidence'] = 'low';
                    $item['isFallback'] = true;
                    $item['displayWarning'] = 'آخرین داده معتبر';
                }
            }
            unset($item);
        }

        return $cached;
    }

    private static function planet_visibility($id, $planet, $ephemeris, $sun, $context)
    {
        $position = is_array($ephemeris['position'] ?? null) ? $ephemeris['position'] : array();
        $appearance = is_array($ephemeris['appearance'] ?? null) ? $ephemeris['appearance'] : array();
        $rise_set = is_array($ephemeris['riseSetTransit'] ?? null) ? $ephemeris['riseSetTransit'] : array();
        $fallback = is_array($ephemeris['fallback'] ?? null) ? $ephemeris['fallback'] : array('isFallback' => true, 'fallbackReason' => 'ephemeris unavailable', 'displayWarning' => 'داده موجود نیست');
        $sun_position = is_array($sun['position'] ?? null) ? $sun['position'] : array();
        $visibility_context = array(
            'sunAltitude' => $sun_position['altitude'] ?? null,
            'moonIllumination' => $context['moonIllumination'] ?? null,
            'cloudCover' => $context['cloudCover'] ?? null,
        );
        $visibility = Jazireh_Visibility_Service::from_conditions($position['altitude'] ?? null, $visibility_context);
        $score = $visibility['score'];
        $is_unavailable = $score === null || !empty($fallback['isFallback']);

        return array(
            'id' => $id,
            'name' => $planet['name'],
            'status' => $is_unavailable ? 'unavailable' : $visibility['status'],
            'statusLabel' => $is_unavailable ? 'داده موجود نیست' : $visibility['label'],
            'visibilityScore' => $is_unavailable ? null : $score,
            'bestTime' => $is_unavailable ? 'داده موجود نیست' : ($visibility['bestWindow'] ?: 'اکنون'),
            'direction' => self::direction_label($position['azimuth'] ?? null),
            'altitudeLabel' => self::altitude_label($position['altitude'] ?? null),
            'brightnessLabel' => self::brightness_label($appearance['magnitude'] ?? null),
            'color' => $planet['color'],
            'summary' => self::summary($planet['name'], $visibility, $position, $fallback),
            'source' => $ephemeris['source'] ?? 'jpl-horizons',
            'accuracy' => $ephemeris['accuracy'] ?? 'unavailable',
            'confidence' => $ephemeris['confidence'] ?? 'low',
            'calculatedAt' => $ephemeris['time']['calculatedAt'] ?? wp_date(DATE_ATOM),
            'generatedAtUtc' => $ephemeris['time']['generatedAtUtc'] ?? gmdate(DATE_ATOM),
            'observer' => $ephemeris['observer'] ?? array(),
            'isFallback' => (bool) ($fallback['isFallback'] ?? false),
            'fallbackReason' => (string) ($fallback['fallbackReason'] ?? ''),
            'displayWarning' => (string) ($fallback['displayWarning'] ?? ''),
            'altitude' => self::number_or_null($position['altitude'] ?? null),
            'azimuth' => self::number_or_null($position['azimuth'] ?? null),
            'rightAscension' => $position['rightAscension'] ?? null,
            'declination' => $position['declination'] ?? null,
            'distance' => self::number_or_null($position['distanceAu'] ?? null),
            'magnitude' => self::number_or_null($appearance['magnitude'] ?? null),
            'rise' => $rise_set['rise'] ?? null,
            'set' => $rise_set['set'] ?? null,
            'transit' => $rise_set['transit'] ?? null,
            'visibilityReason' => $visibility['reason'],
        );
    }

    private static function direction_label($azimuth)
    {
        if ($azimuth === null || !is_numeric($azimuth)) {
            return 'داده موجود نیست';
        }
        $azimuth = fmod(((float) $azimuth + 360), 360);
        $directions = array('شمال', 'شمال شرق', 'شرق', 'جنوب شرق', 'جنوب', 'جنوب غرب', 'غرب', 'شمال غرب');
        return $directions[(int) floor(($azimuth + 22.5) / 45) % 8];
    }

    private static function altitude_label($altitude)
    {
        if ($altitude === null || !is_numeric($altitude)) {
            return 'داده موجود نیست';
        }
        $altitude = (float) $altitude;
        if ($altitude >= 45) {
            return 'بالا';
        }
        if ($altitude >= 20) {
            return 'متوسط';
        }
        if ($altitude > 0) {
            return 'نزدیک افق';
        }
        return 'زیر افق';
    }

    private static function brightness_label($magnitude)
    {
        if ($magnitude === null || !is_numeric($magnitude)) {
            return 'داده موجود نیست';
        }
        $magnitude = (float) $magnitude;
        if ($magnitude <= -2) {
            return 'بسیار درخشان';
        }
        if ($magnitude <= 1) {
            return 'درخشان';
        }
        if ($magnitude <= 4) {
            return 'متوسط';
        }
        return 'کم‌نور';
    }

    private static function summary($name, $visibility, $position, $fallback)
    {
        if (!empty($fallback['isFallback']) || $visibility['score'] === null) {
            return $name . ': داده معتبر افمریس فعلا در دسترس نیست و مقدار ساختگی نمایش داده نمی‌شود.';
        }
        $altitude = isset($position['altitude']) && is_numeric($position['altitude']) ? Jazireh_Astronomy::persian_digits(number_format((float) $position['altitude'], 1, '.', '')) . ' درجه' : 'ناموجود';
        $azimuth = isset($position['azimuth']) && is_numeric($position['azimuth']) ? Jazireh_Astronomy::persian_digits(number_format((float) $position['azimuth'], 1, '.', '')) . ' درجه' : 'ناموجود';
        return $name . ': ' . $visibility['reason'] . ' ارتفاع: ' . $altitude . '، سمت: ' . $azimuth . '.';
    }

    private static function observer($location)
    {
        return array(
            'city' => $location['label'],
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'timezone' => wp_timezone_string(),
        );
    }

    private static function location($settings)
    {
        $city = self::setting_value($settings, 'default_city', 'تهران');
        return array(
            'label' => $city === 'تهران' ? 'تهران، ایران' : $city,
            'latitude' => (float) self::setting_value($settings, 'default_latitude', '35.6892'),
            'longitude' => (float) self::setting_value($settings, 'default_longitude', '51.3890'),
        );
    }

    private static function setting_value($settings, $key, $default = '')
    {
        if (is_array($settings) && array_key_exists($key, $settings) && $settings[$key] !== '') {
            return $settings[$key];
        }
        return $default;
    }

    private static function number_or_null($value)
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private static function sort_by_visibility($left, $right)
    {
        $left_score = $left['visibilityScore'];
        $right_score = $right['visibilityScore'];
        if ($left_score === null && $right_score === null) {
            return strcmp((string) $left['name'], (string) $right['name']);
        }
        if ($left_score === null) {
            return 1;
        }
        if ($right_score === null) {
            return -1;
        }
        return (int) $right_score <=> (int) $left_score;
    }

    private static function payload_cache_key($timestamp, $location, $context)
    {
        $bucket = (int) floor(((int) $timestamp) / (30 * MINUTE_IN_SECONDS)) * (30 * MINUTE_IN_SECONDS);
        $moon_illumination = isset($context['moonIllumination']) && is_numeric($context['moonIllumination']) ? round((float) $context['moonIllumination'], 1) : '';
        $cloud_cover = isset($context['cloudCover']) && is_numeric($context['cloudCover']) ? round((float) $context['cloudCover'], 1) : '';

        return 'jazireh_planets_payload_' . md5(wp_json_encode(array(
            'bucket' => $bucket,
            'location' => $location,
            'timezone' => wp_timezone_string(),
            'moonIllumination' => $moon_illumination,
            'cloudCover' => $cloud_cover,
        )));
    }

    private static function payload_cache_ttl($payload)
    {
        return !empty($payload['isFallback']) ? 10 * MINUTE_IN_SECONDS : 30 * MINUTE_IN_SECONDS;
    }
}
