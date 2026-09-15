<?php

if (!defined('ABSPATH')) {
    exit;
}

class Jazireh_Astronomy
{
    private const SYNODIC_MONTH = 29.530588853;
    private const KNOWN_NEW_MOON_JD = 2451550.1;

    public static function sky_payload($for_home = false)
    {
        $settings = get_option(Jazireh_Settings::OPTION_NAME, array());
        $location = self::location($settings);
        $now = current_time('timestamp', true);
        $cache_key = self::sky_cache_key($for_home, $location, $now);
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $moon = self::moon($now, $location);
        $sun = self::sun($now, $location);
        $weather = self::weather($location['latitude'], $location['longitude']);
        $planets = Jazireh_Planets::payload($now, array(
            'moonIllumination' => $moon['illumination'],
            'cloudCover' => $weather['cloudCover'],
        ));
        $upcoming_events = Jazireh_Events::payload(5, 60, $planets);
        $metadata = self::metadata(
            $now,
            $location,
            !empty($sun['isFallback']) || !empty($weather['isFallback']) || !empty($moon['isFallback']),
            !empty($moon['fallbackReason']) ? $moon['fallbackReason'] : (!empty($sun['fallbackReason']) ? $sun['fallbackReason'] : $weather['fallbackReason']),
            !empty($moon['displayWarning']) ? $moon['displayWarning'] : (!empty($sun['displayWarning']) ? $sun['displayWarning'] : $weather['displayWarning'])
        );

        $payload = array(
            'id' => 1,
            'status' => 'ready',
            'location' => $location['label'],
            'locationLabel' => 'محاسبه برای ' . $location['label'],
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'source' => $metadata['source'],
            'accuracy' => $metadata['accuracy'],
            'confidence' => $metadata['confidence'],
            'calculatedAt' => $metadata['calculatedAt'],
            'generatedAtUtc' => $metadata['generatedAtUtc'],
            'timezone' => $metadata['timezone'],
            'locationMeta' => $metadata['location'],
            'isFallback' => $metadata['isFallback'],
            'fallbackReason' => $metadata['fallbackReason'],
            'displayWarning' => $metadata['displayWarning'],
            'temperature' => $weather['temperature'],
            'condition' => $weather['condition'],
            'humidity' => $weather['humidity'],
            'wind' => $weather['wind'],
            'pressure' => $weather['pressure'],
            'visibility' => $weather['visibility'],
            'cloudCover' => $weather['cloudCover'],
            'weather' => $weather,
            'moonPhase' => $moon['phase'],
            'moonIllumination' => $moon['illumination'],
            'moonAge' => $moon['age'],
            'moonTrend' => $moon['trend'],
            'moonAccuracy' => $moon['accuracy'],
            'moonSource' => $moon['source'],
            'moonConfidence' => $moon['confidence'],
            'moonIsFallback' => $moon['isFallback'],
            'moonFallbackReason' => $moon['fallbackReason'],
            'moonDisplayWarning' => $moon['displayWarning'],
            'moonAltitude' => $moon['altitude'],
            'moonAzimuth' => $moon['azimuth'],
            'moonRightAscension' => $moon['rightAscension'],
            'moonDeclination' => $moon['declination'],
            'moonDistance' => $moon['distance'],
            'moonRise' => $moon['rise'],
            'moonSet' => $moon['set'],
            'moonTransit' => $moon['transit'],
            'moonPhaseAngle' => $moon['phaseAngle'],
            'moonEphemeris' => $moon['ephemeris'],
            'daysToFullMoon' => $moon['daysToFullMoon'],
            'daysToNewMoon' => $moon['daysToNewMoon'],
            'sunrise' => $sun['sunrise'],
            'sunset' => $sun['sunset'],
            'sunAltitude' => $sun['altitude'],
            'sunAzimuth' => $sun['azimuth'],
            'sunRightAscension' => $sun['rightAscension'],
            'sunDeclination' => $sun['declination'],
            'sunDistance' => $sun['distance'],
            'sunApparentMagnitude' => $sun['apparentMagnitude'],
            'sunTransit' => $sun['transit'],
            'sunTimes' => array(
                'status' => $sun['status'],
                'source' => $sun['source'],
                'accuracy' => $sun['accuracy'],
                'confidence' => $sun['confidence'],
                'calculatedAt' => $sun['calculatedAt'],
                'generatedAtUtc' => $sun['generatedAtUtc'],
                'observer' => $sun['observer'],
                'timezone' => $sun['timezone'],
                'isFallback' => $sun['isFallback'],
                'fallbackReason' => $sun['fallbackReason'],
                'displayWarning' => $sun['displayWarning'],
                'message' => $sun['message'],
                'altitude' => $sun['altitude'],
                'azimuth' => $sun['azimuth'],
                'rightAscension' => $sun['rightAscension'],
                'declination' => $sun['declination'],
                'distance' => $sun['distance'],
                'apparentMagnitude' => $sun['apparentMagnitude'],
                'transit' => $sun['transit'],
                'ephemeris' => $sun['ephemeris'],
            ),
            'bestTime' => self::best_observation_window($sun['sunsetTimestamp'], $moon['illumination']),
            'seeing' => null,
            'transparency' => null,
            'observingCondition' => self::observing_condition($weather, $moon['illumination']),
            'events' => self::events($sun, $moon),
            'planets' => $planets,
            'upcomingEvents' => $upcoming_events,
            'observed_at' => wp_date('Y-m-d H:i:s', $now),
            'generatedAt' => wp_date(DATE_ATOM, $now),
            'freshness' => array(
                'moon' => $moon['status'],
                'sun' => $sun['status'],
                'weather' => $weather['status'],
            ),
            'message' => $moon['displayWarning'] ?: ($sun['displayWarning'] ?: ($weather['displayWarning'] ?: 'داده‌های ماه و موقعیت خورشید از JPL Horizons، زمان طلوع و غروب از محاسبه معتبر سایت و وضعیت هوا از Open-Meteo دریافت می‌شود.')),
        );

        $payload['tonightHighlights'] = Jazireh_Events::tonight_highlights($payload, $planets, $upcoming_events);

        if ($for_home) {
            unset($payload['id'], $payload['latitude'], $payload['longitude'], $payload['events'], $payload['planets'], $payload['upcomingEvents'], $payload['tonightHighlights']);
        }

        set_transient($cache_key, $payload, self::sky_cache_ttl($payload));

        return $payload;
    }

    public static function persian_digits($value)
    {
        return strtr((string) $value, array(
            '0' => '۰',
            '1' => '۱',
            '2' => '۲',
            '3' => '۳',
            '4' => '۴',
            '5' => '۵',
            '6' => '۶',
            '7' => '۷',
            '8' => '۸',
            '9' => '۹',
        ));
    }

    private static function location($settings)
    {
        $city = self::setting_value($settings, 'default_city', 'تهران');
        $label = $city === 'تهران' ? 'تهران، ایران' : $city;

        return array(
            'label' => $label,
            'latitude' => (float) self::setting_value($settings, 'default_latitude', '35.6892'),
            'longitude' => (float) self::setting_value($settings, 'default_longitude', '51.3890'),
        );
    }

    private static function metadata($timestamp, $location, $is_fallback = false, $fallback_reason = '', $display_warning = '')
    {
        return array(
            'source' => $is_fallback ? 'fallback' : 'open-meteo,date_sun_info,jpl-horizons',
            'accuracy' => 'mixed',
            'confidence' => $is_fallback ? 'low' : 'medium',
            'calculatedAt' => wp_date(DATE_ATOM, $timestamp),
            'generatedAtUtc' => gmdate(DATE_ATOM, $timestamp),
            'timezone' => wp_timezone_string(),
            'location' => array(
                'city' => $location['label'],
                'lat' => $location['latitude'],
                'lng' => $location['longitude'],
            ),
            'isFallback' => (bool) $is_fallback,
            'fallbackReason' => $fallback_reason,
            'displayWarning' => $display_warning,
        );
    }

    private static function weather($latitude, $longitude)
    {
        $cache_key = 'jazireh_weather_' . md5(round((float) $latitude, 3) . '_' . round((float) $longitude, 3) . '_' . wp_date('Y-m-d-H'));
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $fallback = self::weather_unavailable('سرویس هواشناسی در دسترس نیست؛ داده زنده هوا نمایش داده نمی‌شود.');
        $url = add_query_arg(array(
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
            'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,pressure_msl,cloud_cover',
            'timezone' => self::weather_timezone_param(),
        ), 'https://api.open-meteo.com/v1/forecast');

        $response = wp_remote_get($url, array('timeout' => 8, 'redirection' => 2));
        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            set_transient($cache_key, $fallback, 20 * MINUTE_IN_SECONDS);
            return $fallback;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $current = is_array($body) && isset($body['current']) && is_array($body['current']) ? $body['current'] : array();
        if (empty($current)) {
            set_transient($cache_key, $fallback, 20 * MINUTE_IN_SECONDS);
            return $fallback;
        }

        $payload = array(
            'status' => 'ready',
            'source' => 'open-meteo',
            'accuracy' => 'forecast',
            'confidence' => 'medium',
            'temperature' => isset($current['temperature_2m']) ? number_format((float) $current['temperature_2m'], 1, '.', '') : null,
            'condition' => isset($current['cloud_cover']) ? self::cloud_condition((float) $current['cloud_cover']) : 'وضعیت هوا دریافت شد',
            'humidity' => isset($current['relative_humidity_2m']) ? (int) round((float) $current['relative_humidity_2m']) : null,
            'wind' => isset($current['wind_speed_10m']) ? number_format((float) $current['wind_speed_10m'], 1, '.', '') : null,
            'pressure' => isset($current['pressure_msl']) ? (int) round((float) $current['pressure_msl']) : null,
            'visibility' => null,
            'cloudCover' => isset($current['cloud_cover']) ? (int) round((float) $current['cloud_cover']) : null,
            'calculatedAt' => wp_date(DATE_ATOM),
            'timezone' => wp_timezone_string(),
            'isFallback' => false,
            'fallbackReason' => '',
            'displayWarning' => '',
        );
        set_transient($cache_key, $payload, 30 * MINUTE_IN_SECONDS);
        return $payload;
    }

    private static function weather_unavailable($reason)
    {
        return array(
            'status' => 'stale',
            'source' => 'none',
            'accuracy' => 'unavailable',
            'confidence' => 'low',
            'temperature' => null,
            'condition' => 'داده زنده هوا در دسترس نیست',
            'humidity' => null,
            'wind' => null,
            'pressure' => null,
            'visibility' => null,
            'cloudCover' => null,
            'calculatedAt' => wp_date(DATE_ATOM),
            'timezone' => wp_timezone_string(),
            'isFallback' => true,
            'fallbackReason' => $reason,
            'displayWarning' => 'داده پشتیبان: وضعیت واقعی هوا فعلا دریافت نشده است.',
        );
    }

    private static function weather_timezone_param()
    {
        $timezone = wp_timezone_string();
        return preg_match('/^[+-]\d{2}:\d{2}$/', $timezone) ? 'auto' : $timezone;
    }

    private static function cloud_condition($cloud_cover)
    {
        if ($cloud_cover <= 20) {
            return 'آسمان کم‌ابر';
        }
        if ($cloud_cover <= 55) {
            return 'ابرناکی متوسط';
        }
        return 'آسمان ابری';
    }

    private static function moon($timestamp, $location)
    {
        $observer = array(
            'city' => $location['label'],
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'timezone' => wp_timezone_string(),
        );
        $ephemeris = Jazireh_Ephemeris::object('moon', $observer, $timestamp, 'moon-current');
        if (empty($ephemeris['status']) || $ephemeris['status'] === 'unavailable') {
            return self::moon_unavailable($ephemeris);
        }

        $illumination = $ephemeris['appearance']['illumination'] ?? null;
        $phase_angle = $ephemeris['appearance']['phaseAngle'] ?? null;
        $trend = self::moon_trend_from_ephemeris($observer, $timestamp, $illumination);
        $age = self::moon_age_from_phase_angle($phase_angle, $trend);
        $full_age = self::SYNODIC_MONTH / 2;
        $days_to_full = $age !== null ? ($age <= $full_age ? $full_age - $age : self::SYNODIC_MONTH - $age + $full_age) : null;
        $days_to_new = $age !== null ? self::SYNODIC_MONTH - $age : null;
        if ($days_to_new !== null && $days_to_new > self::SYNODIC_MONTH - 0.25) {
            $days_to_new = 0;
        }

        return array(
            'phase' => self::moon_phase_name($age),
            'illumination' => $illumination !== null ? number_format((float) $illumination, 1, '.', '') : null,
            'age' => $age !== null ? number_format($age, 1, '.', '') : null,
            'trend' => $trend,
            'daysToFullMoon' => $days_to_full !== null ? number_format($days_to_full, 1, '.', '') : null,
            'daysToNewMoon' => $days_to_new !== null ? number_format($days_to_new, 1, '.', '') : null,
            'status' => $ephemeris['status'],
            'source' => $ephemeris['source'],
            'accuracy' => 'jpl_horizons_authoritative_position_derived_phase_age',
            'confidence' => $ephemeris['confidence'],
            'isFallback' => !empty($ephemeris['fallback']['isFallback']),
            'fallbackReason' => $ephemeris['fallback']['fallbackReason'] ?? '',
            'displayWarning' => $ephemeris['fallback']['displayWarning'] ?? '',
            'altitude' => $ephemeris['position']['altitude'] ?? null,
            'azimuth' => $ephemeris['position']['azimuth'] ?? null,
            'rightAscension' => $ephemeris['position']['rightAscension'] ?? null,
            'declination' => $ephemeris['position']['declination'] ?? null,
            'distance' => $ephemeris['position']['distanceAu'] ?? null,
            'rise' => $ephemeris['riseSetTransit']['rise'] ?? null,
            'set' => $ephemeris['riseSetTransit']['set'] ?? null,
            'transit' => $ephemeris['riseSetTransit']['transit'] ?? null,
            'phaseAngle' => $phase_angle !== null ? (float) $phase_angle : null,
            'ephemeris' => array(
                'status' => $ephemeris['status'],
                'source' => $ephemeris['source'],
                'accuracy' => $ephemeris['accuracy'],
                'confidence' => $ephemeris['confidence'],
                'calculatedAt' => $ephemeris['time']['calculatedAt'] ?? null,
                'generatedAtUtc' => $ephemeris['time']['generatedAtUtc'] ?? null,
                'observer' => $ephemeris['observer'] ?? array(),
                'isFallback' => !empty($ephemeris['fallback']['isFallback']),
                'fallbackReason' => $ephemeris['fallback']['fallbackReason'] ?? '',
                'displayWarning' => $ephemeris['fallback']['displayWarning'] ?? '',
                'riseSetTransitStatus' => $ephemeris['riseSetTransit']['status'] ?? 'not-calculated',
            ),
        );
    }

    private static function moon_phase_name($age)
    {
        if ($age === null) {
            return 'در دسترس نیست';
        }
        if ($age < 1.84566) {
            return 'ماه نو';
        }
        if ($age < 5.53699) {
            return 'هلال افزایشی';
        }
        if ($age < 9.22831) {
            return 'تربیع اول';
        }
        if ($age < 12.91963) {
            return 'کوژ افزایشی';
        }
        if ($age < 16.61096) {
            return 'ماه بدر';
        }
        if ($age < 20.30228) {
            return 'کوژ کاهشی';
        }
        if ($age < 23.99361) {
            return 'تربیع آخر';
        }
        if ($age < 27.68493) {
            return 'هلال کاهشی';
        }
        return 'ماه نو';
    }

    private static function moon_age_from_phase_angle($phase_angle, $trend)
    {
        if ($phase_angle === null || !is_numeric($phase_angle) || !$trend) {
            return null;
        }
        $phase_angle = max(0, min(180, (float) $phase_angle));
        if ($trend === 'افزایشی') {
            return ((180 - $phase_angle) / 360) * self::SYNODIC_MONTH;
        }
        return ((180 + $phase_angle) / 360) * self::SYNODIC_MONTH;
    }

    private static function moon_trend_from_ephemeris($observer, $timestamp, $illumination)
    {
        if ($illumination === null || !is_numeric($illumination)) {
            return null;
        }
        $next = Jazireh_Ephemeris::object('moon', $observer, $timestamp + HOUR_IN_SECONDS, 'moon-trend');
        $next_illumination = $next['appearance']['illumination'] ?? null;
        if ($next_illumination === null || !is_numeric($next_illumination)) {
            return null;
        }
        return (float) $next_illumination >= (float) $illumination ? 'افزایشی' : 'کاهشی';
    }

    private static function moon_unavailable($ephemeris)
    {
        return array(
            'phase' => 'در دسترس نیست',
            'illumination' => null,
            'age' => null,
            'trend' => null,
            'daysToFullMoon' => null,
            'daysToNewMoon' => null,
            'status' => 'unavailable',
            'source' => $ephemeris['source'] ?? 'jpl-horizons',
            'accuracy' => 'unavailable',
            'confidence' => 'low',
            'isFallback' => true,
            'fallbackReason' => $ephemeris['fallback']['fallbackReason'] ?? 'moon ephemeris unavailable',
            'displayWarning' => 'داده معتبر ماه از JPL Horizons فعلا در دسترس نیست.',
            'altitude' => null,
            'azimuth' => null,
            'rightAscension' => null,
            'declination' => null,
            'distance' => null,
            'rise' => null,
            'set' => null,
            'transit' => null,
            'phaseAngle' => null,
            'ephemeris' => array(
                'status' => 'unavailable',
                'source' => $ephemeris['source'] ?? 'jpl-horizons',
                'accuracy' => 'unavailable',
                'confidence' => 'low',
                'calculatedAt' => $ephemeris['time']['calculatedAt'] ?? wp_date(DATE_ATOM),
                'generatedAtUtc' => $ephemeris['time']['generatedAtUtc'] ?? gmdate(DATE_ATOM),
                'observer' => $ephemeris['observer'] ?? array(),
                'isFallback' => true,
                'fallbackReason' => $ephemeris['fallback']['fallbackReason'] ?? 'moon ephemeris unavailable',
                'displayWarning' => 'داده معتبر ماه از JPL Horizons فعلا در دسترس نیست.',
                'riseSetTransitStatus' => 'unavailable',
            ),
        );
    }

    private static function sun($timestamp, $location)
    {
        $latitude = $location['latitude'];
        $longitude = $location['longitude'];
        $observer = array(
            'city' => $location['label'],
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timezone' => wp_timezone_string(),
        );
        $ephemeris = Jazireh_Ephemeris::object('sun', $observer, $timestamp, 'sun-current');
        $position = is_array($ephemeris['position'] ?? null) ? $ephemeris['position'] : array();
        $appearance = is_array($ephemeris['appearance'] ?? null) ? $ephemeris['appearance'] : array();
        $fallback = is_array($ephemeris['fallback'] ?? null) ? $ephemeris['fallback'] : array(
            'isFallback' => true,
            'fallbackReason' => 'sun ephemeris unavailable',
            'displayWarning' => 'داده معتبر موقعیت خورشید فعلا در دسترس نیست.',
        );
        $info = function_exists('date_sun_info') ? date_sun_info($timestamp, $latitude, $longitude) : false;
        $sunrise = is_array($info) && !empty($info['sunrise']) ? (int) $info['sunrise'] : null;
        $sunset = is_array($info) && !empty($info['sunset']) ? (int) $info['sunset'] : null;
        $sun_times_ready = (bool) ($sunrise && $sunset);
        $ephemeris_ready = !empty($ephemeris['status']) && $ephemeris['status'] !== 'unavailable' && empty($fallback['isFallback']);
        $is_fallback = !$sun_times_ready || !$ephemeris_ready;
        $fallback_reason = '';
        if (!$sun_times_ready) {
            $fallback_reason = 'sunrise/sunset calculation unavailable';
        } elseif (!$ephemeris_ready) {
            $fallback_reason = (string) ($fallback['fallbackReason'] ?? 'sun ephemeris unavailable');
        }
        $display_warning = '';
        if (!$sun_times_ready) {
            $display_warning = 'زمان طلوع و غروب در حال حاضر در دسترس نیست.';
        } elseif (!$ephemeris_ready) {
            $display_warning = 'داده معتبر موقعیت خورشید فعلا در دسترس نیست.';
        }

        return array(
            'status' => ($sun_times_ready && $ephemeris_ready) ? 'ready' : 'stale',
            'sunrise' => $sunrise ? self::persian_digits(wp_date('H:i', $sunrise)) : null,
            'sunset' => $sunset ? self::persian_digits(wp_date('H:i', $sunset)) : null,
            'sunriseTimestamp' => $sunrise,
            'sunsetTimestamp' => $sunset,
            'source' => $ephemeris_ready ? 'jpl-horizons,date_sun_info' : 'date_sun_info,jpl-horizons',
            'accuracy' => ($sun_times_ready && $ephemeris_ready) ? 'authoritative-position-calculated-rise-set' : 'mixed-unavailable',
            'confidence' => ($sun_times_ready && $ephemeris_ready) ? 'high' : 'low',
            'calculatedAt' => $ephemeris['time']['calculatedAt'] ?? wp_date(DATE_ATOM, $timestamp),
            'generatedAtUtc' => $ephemeris['time']['generatedAtUtc'] ?? gmdate(DATE_ATOM, $timestamp),
            'observer' => $ephemeris['observer'] ?? Jazireh_Ephemeris::normalize_observer($observer),
            'timezone' => wp_timezone_string(),
            'isFallback' => $is_fallback,
            'fallbackReason' => $fallback_reason,
            'displayWarning' => $display_warning,
            'message' => $display_warning,
            'altitude' => $ephemeris_ready ? ($position['altitude'] ?? null) : null,
            'azimuth' => $ephemeris_ready ? ($position['azimuth'] ?? null) : null,
            'rightAscension' => $ephemeris_ready ? ($position['rightAscension'] ?? null) : null,
            'declination' => $ephemeris_ready ? ($position['declination'] ?? null) : null,
            'distance' => $ephemeris_ready ? ($position['distanceAu'] ?? null) : null,
            'apparentMagnitude' => $ephemeris_ready ? ($appearance['magnitude'] ?? null) : null,
            'transit' => null,
            'ephemeris' => array(
                'status' => $ephemeris['status'] ?? 'unavailable',
                'source' => $ephemeris['source'] ?? 'jpl-horizons',
                'accuracy' => $ephemeris['accuracy'] ?? 'unavailable',
                'confidence' => $ephemeris['confidence'] ?? 'low',
                'calculatedAt' => $ephemeris['time']['calculatedAt'] ?? wp_date(DATE_ATOM, $timestamp),
                'generatedAtUtc' => $ephemeris['time']['generatedAtUtc'] ?? gmdate(DATE_ATOM, $timestamp),
                'observer' => $ephemeris['observer'] ?? Jazireh_Ephemeris::normalize_observer($observer),
                'isFallback' => !$ephemeris_ready,
                'fallbackReason' => $ephemeris_ready ? '' : (string) ($fallback['fallbackReason'] ?? 'sun ephemeris unavailable'),
                'displayWarning' => $ephemeris_ready ? '' : 'داده معتبر موقعیت خورشید فعلا در دسترس نیست.',
                'riseSetTransitStatus' => 'transit-not-calculated',
            ),
        );
    }

    private static function best_observation_window($sunset_timestamp, $illumination)
    {
        if (!$sunset_timestamp) {
            return null;
        }
        $start = $sunset_timestamp + (90 * MINUTE_IN_SECONDS);
        $end_hour = ((float) $illumination > 75) ? '۰۱:۳۰' : '۰۳:۳۰';
        return self::persian_digits(wp_date('H:i', $start)) . ' تا ' . $end_hour;
    }

    private static function observing_condition($weather, $illumination)
    {
        $cloud_cover = isset($weather['cloudCover']) && $weather['cloudCover'] !== null ? (int) $weather['cloudCover'] : null;
        if ($cloud_cover === null) {
            return array(
                'status' => 'estimated',
                'label' => 'نیازمند بررسی محلی',
                'summary' => 'شرایط رصد بدون داده زنده ابرناکی و هوا قابل قضاوت دقیق نیست.',
                'source' => 'jpl-horizons',
                'accuracy' => 'estimated',
                'confidence' => 'low',
                'isFallback' => true,
                'fallbackReason' => 'داده هواشناسی دریافت نشد.',
                'displayWarning' => 'نمایش تقریبی: فقط اثر روشنایی ماه در نظر گرفته شده است.',
            );
        }

        $moon_penalty = ((float) $illumination > 65) ? 1 : 0;
        if ($cloud_cover <= 25 && !$moon_penalty) {
            $label = 'مناسب برای رصد عمومی';
        } elseif ($cloud_cover <= 55) {
            $label = 'متوسط';
        } else {
            $label = 'محدود';
        }

        return array(
            'status' => 'estimated',
            'label' => $label,
            'summary' => 'این شاخص از ابرناکی Open-Meteo و روشنایی ماه ساخته شده و جایگزین seeing تلسکوپی نیست.',
            'source' => 'open-meteo,jpl-horizons',
            'accuracy' => 'estimated',
            'confidence' => 'medium',
            'isFallback' => false,
            'fallbackReason' => '',
            'displayWarning' => 'نمایش تقریبی: seeing و transparency واقعی اندازه‌گیری نشده‌اند.',
        );
    }

    private static function events($sun, $moon)
    {
        $sunset = !empty($sun['sunsetTimestamp']) ? self::persian_digits(wp_date('H:i', $sun['sunsetTimestamp'] + (45 * MINUTE_IN_SECONDS))) : 'پس از غروب';
        $best = !empty($sun['sunsetTimestamp']) ? self::persian_digits(wp_date('H:i', $sun['sunsetTimestamp'] + (90 * MINUTE_IN_SECONDS))) : 'شب';
        $moon_detail = $moon['illumination'] !== null
            ? 'فاز فعلی: ' . $moon['phase'] . ' با روشنایی ' . self::persian_digits($moon['illumination']) . '٪.'
            : 'داده معتبر ماه در حال حاضر در دسترس نیست.';
        $dark_window_detail = $moon['illumination'] !== null
            ? (((float) $moon['illumination'] > 65) ? 'نور ماه زیاد است؛ اجرام پرنور و سیاره‌ها انتخاب بهتری هستند.' : 'شرایط برای اجرام کم‌نورتر مناسب‌تر است.')
            : 'اثر نور ماه فعلا قابل محاسبه نیست؛ وضعیت آسمان را با مشاهده محلی بررسی کنید.';
        $next_moon_detail = ($moon['daysToFullMoon'] !== null && $moon['daysToNewMoon'] !== null)
            ? 'تا بدر بعدی حدود ' . self::persian_digits($moon['daysToFullMoon']) . ' روز و تا ماه نو بعدی حدود ' . self::persian_digits($moon['daysToNewMoon']) . ' روز مانده است.'
            : 'زمان فازهای بعدی ماه در حال حاضر محاسبه نشده است.';

        return array(
            array(
                'title' => 'رصد ماه',
                'time' => $sunset,
                'detail' => $moon_detail,
            ),
            array(
                'title' => 'پنجره تاریکی آسمان',
                'time' => $best,
                'detail' => $dark_window_detail,
            ),
            array(
                'title' => 'وضعیت ماه بعدی',
                'time' => 'امشب',
                'detail' => $next_moon_detail,
            ),
        );
    }

    private static function setting_value($settings, $key, $default = '')
    {
        if (is_array($settings) && array_key_exists($key, $settings) && $settings[$key] !== '') {
            return $settings[$key];
        }
        return $default;
    }

    private static function sky_cache_key($for_home, $location, $timestamp)
    {
        $bucket = (int) floor(((int) $timestamp) / (15 * MINUTE_IN_SECONDS)) * (15 * MINUTE_IN_SECONDS);
        return 'jazireh_sky_payload_' . md5(wp_json_encode(array(
            'home' => (bool) $for_home,
            'location' => array(
                'label' => (string) ($location['label'] ?? ''),
                'latitude' => round((float) ($location['latitude'] ?? 0), 3),
                'longitude' => round((float) ($location['longitude'] ?? 0), 3),
            ),
            'timezone' => wp_timezone_string(),
            'bucket' => $bucket,
        )));
    }

    private static function sky_cache_ttl($payload)
    {
        return !empty($payload['isFallback']) ? 5 * MINUTE_IN_SECONDS : 15 * MINUTE_IN_SECONDS;
    }
}
