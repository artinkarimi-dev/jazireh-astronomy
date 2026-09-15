<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Sky_Service
{
    const WIDGET_KEY = 'sky';
    const CACHE_TTL = 900;
    const ZENITH_OFFICIAL = 90.833333;
    const ZENITH_ASTRONOMICAL = 108;

    public static function widget()
    {
        $cached = Jazireh_Widgets::get_cached(self::WIDGET_KEY);
        if (is_array($cached) && !Jazireh_Widgets::is_stale($cached)) {
            return $cached;
        }

        $payload = self::calculate_payload(current_time('timestamp', true));
        if (is_wp_error($payload)) {
            return Jazireh_Widgets::error(self::WIDGET_KEY, $payload->get_error_message(), array(
                'source' => 'Astronomical calculation',
                'sourceUrl' => '',
            ));
        }

        $now = current_time('timestamp', true);
        $response = Jazireh_Widgets::ready(self::WIDGET_KEY, $payload, array(
            'updatedAt' => $now,
            'expiresAt' => $now + self::CACHE_TTL,
            'source' => 'Astronomical calculation',
            'sourceUrl' => '',
        ));

        return Jazireh_Widgets::set_cached(self::WIDGET_KEY, $response, self::CACHE_TTL);
    }

    public static function refresh()
    {
        Jazireh_Widgets::delete_cached(self::WIDGET_KEY);
        return self::widget();
    }

    public static function sky_today_payload()
    {
        return self::compatibility_payload(false);
    }

    public static function compatibility_payload($for_home = false)
    {
        $widget = self::widget();
        $data = is_array($widget) && !empty($widget['data']) && is_array($widget['data']) ? $widget['data'] : array();
        $moon = isset($data['moonStatus']) && is_array($data['moonStatus']) ? $data['moonStatus'] : array();
        $location = isset($data['location']) && is_array($data['location']) ? $data['location'] : array();
        $status = isset($widget['status']) ? sanitize_key((string) $widget['status']) : 'error';
        $message = isset($widget['message']) ? (string) $widget['message'] : '';
        $calculated_at = isset($data['calculatedAt']) ? $data['calculatedAt'] : (isset($widget['updatedAt']) ? $widget['updatedAt'] : '');
        $highlights = isset($data['visibleHighlights']) && is_array($data['visibleHighlights']) ? $data['visibleHighlights'] : array();

        $payload = array(
            'id' => 1,
            'status' => $status,
            'location' => isset($location['name']) ? $location['name'] : 'تهران، ایران',
            'locationLabel' => 'محاسبه برای ' . (isset($location['name']) ? $location['name'] : 'تهران، ایران'),
            'latitude' => isset($location['latitude']) ? (string) $location['latitude'] : '35.6892',
            'longitude' => isset($location['longitude']) ? (string) $location['longitude'] : '51.3890',
            'source' => isset($widget['source']) ? $widget['source'] : 'Astronomical calculation',
            'accuracy' => 'calculated',
            'confidence' => $status === Jazireh_Widgets::STATE_READY ? 'medium' : 'low',
            'calculatedAt' => $calculated_at,
            'generatedAtUtc' => $calculated_at,
            'timezone' => isset($location['timezone']) ? $location['timezone'] : wp_timezone_string(),
            'locationMeta' => array(
                'city' => isset($location['name']) ? $location['name'] : 'تهران، ایران',
                'lat' => isset($location['latitude']) ? (float) $location['latitude'] : 35.6892,
                'lng' => isset($location['longitude']) ? (float) $location['longitude'] : 51.3890,
            ),
            'isFallback' => $status !== Jazireh_Widgets::STATE_READY,
            'fallbackReason' => $status === Jazireh_Widgets::STATE_READY ? '' : $message,
            'displayWarning' => $status === Jazireh_Widgets::STATE_READY ? '' : $message,
            'temperature' => null,
            'condition' => isset($data['conditionLabelFa']) ? $data['conditionLabelFa'] : 'محاسبه نجومی',
            'humidity' => null,
            'wind' => null,
            'pressure' => null,
            'visibility' => null,
            'cloudCover' => null,
            'weather' => array(
                'status' => 'unavailable',
                'source' => 'none',
                'accuracy' => 'unavailable',
                'confidence' => 'low',
                'temperature' => null,
                'condition' => 'داده زنده هوا در این خلاصه محاسبه نمی‌شود',
                'humidity' => null,
                'wind' => null,
                'pressure' => null,
                'visibility' => null,
                'cloudCover' => null,
                'isFallback' => true,
                'fallbackReason' => 'Home uses the lightweight sky widget contract without weather fetches.',
                'displayWarning' => 'داده هواشناسی زنده در این خلاصه دریافت نمی‌شود.',
            ),
            'moonPhase' => isset($moon['phaseLabelFa']) ? $moon['phaseLabelFa'] : '',
            'moonIllumination' => isset($moon['illuminationPercent']) ? number_format_i18n((float) $moon['illuminationPercent'], 1) : '',
            'moonAge' => isset($moon['moonAgeDays']) ? number_format_i18n((float) $moon['moonAgeDays'], 1) : '',
            'moonTrend' => isset($moon['waxingWaningLabelFa']) ? $moon['waxingWaningLabelFa'] : null,
            'daysToFullMoon' => isset($data['moonPhaseReference']['nextFullMoon']) ? self::days_until($data['moonPhaseReference']['nextFullMoon']) : null,
            'daysToNewMoon' => isset($data['moonPhaseReference']['nextNewMoon']) ? self::days_until($data['moonPhaseReference']['nextNewMoon']) : null,
            'sunrise' => isset($data['sunrise']['localTime']) ? $data['sunrise']['localTime'] : '',
            'sunset' => isset($data['sunset']['localTime']) ? $data['sunset']['localTime'] : '',
            'sunTimes' => array(
                'status' => $status,
                'source' => isset($widget['source']) ? $widget['source'] : 'Astronomical calculation',
                'accuracy' => 'calculated',
                'confidence' => $status === Jazireh_Widgets::STATE_READY ? 'medium' : 'low',
                'message' => $message,
            ),
            'bestTime' => isset($data['bestObservationWindow']['labelFa']) ? $data['bestObservationWindow']['labelFa'] : '',
            'seeing' => isset($data['observationScore']['seeing']) ? (string) $data['observationScore']['seeing'] : null,
            'transparency' => isset($data['observationScore']['transparency']) ? (string) $data['observationScore']['transparency'] : null,
            'observingCondition' => array(
                'status' => 'estimated',
                'label' => isset($data['conditionLabelFa']) ? $data['conditionLabelFa'] : 'محاسبه نجومی',
                'summary' => 'این خلاصه از محاسبات سبک رصدخانه زنده ساخته شده است.',
                'source' => isset($widget['source']) ? $widget['source'] : 'Astronomical calculation',
                'accuracy' => 'calculated',
                'confidence' => $status === Jazireh_Widgets::STATE_READY ? 'medium' : 'low',
                'isFallback' => $status !== Jazireh_Widgets::STATE_READY,
                'fallbackReason' => $status === Jazireh_Widgets::STATE_READY ? '' : $message,
                'displayWarning' => '',
            ),
            'events' => $highlights,
            'planets' => self::unavailable_collection(
                'planets',
                'داده موقعیت سیارات در قرارداد سبک آسمان امروز محاسبه نمی‌شود.',
                $location
            ),
            'upcomingEvents' => array(
                'status' => 'unavailable',
                'source' => 'none',
                'accuracy' => 'unavailable',
                'confidence' => 'low',
                'location' => self::collection_location($location),
                'items' => array(),
                'isFallback' => false,
                'message' => 'داده رویدادهای آینده در قرارداد سبک آسمان امروز محاسبه نمی‌شود.',
                'displayWarning' => 'رویدادهای آینده در این نسخه از داده سبک آسمان امروز در دسترس نیست.',
            ),
            'tonightHighlights' => self::home_highlights($data),
            'observed_at' => $calculated_at,
            'message' => $message,
        );

        if ($for_home) {
            unset($payload['id'], $payload['latitude'], $payload['longitude'], $payload['moonAge'], $payload['seeing'], $payload['events']);
        }

        return $payload;
    }

    private static function unavailable_collection($key, $message, $location)
    {
        return array(
            'status' => 'unavailable',
            'source' => 'none',
            'accuracy' => 'unavailable',
            'confidence' => 'low',
            'location' => self::collection_location($location),
            'items' => array(),
            'isFallback' => false,
            'message' => $message,
            'displayWarning' => $message,
            'key' => sanitize_key($key),
        );
    }

    private static function collection_location($location)
    {
        return array(
            'label' => isset($location['name']) ? $location['name'] : 'تهران، ایران',
            'city' => isset($location['name']) ? $location['name'] : 'تهران، ایران',
            'latitude' => isset($location['latitude']) ? (float) $location['latitude'] : 35.6892,
            'longitude' => isset($location['longitude']) ? (float) $location['longitude'] : 51.3890,
            'timezone' => isset($location['timezone']) ? $location['timezone'] : wp_timezone_string(),
        );
    }

    private static function days_until($value)
    {
        $timestamp = strtotime((string) $value);
        if (!$timestamp) {
            return null;
        }

        $days = (int) ceil(($timestamp - current_time('timestamp', true)) / DAY_IN_SECONDS);
        return max(0, $days);
    }

    private static function home_highlights($data)
    {
        $highlights = array();

        if (!empty($data['bestObservationWindow']['labelFa'])) {
            $highlights[] = array(
                'id' => 'best-window',
                'title' => 'بهترین پنجره رصد',
                'value' => $data['bestObservationWindow']['labelFa'],
                'summary' => isset($data['bestObservationWindow']['basis']) ? $data['bestObservationWindow']['basis'] : '',
                'source' => 'Astronomical calculation',
                'confidence' => 'medium',
            );
        }

        $moon = isset($data['moonStatus']) && is_array($data['moonStatus']) ? $data['moonStatus'] : array();
        if (!empty($moon['phaseLabelFa']) || isset($moon['illuminationPercent'])) {
            $illumination = isset($moon['illuminationPercent']) ? number_format_i18n((float) $moon['illuminationPercent'], 1) . '٪' : 'ناموجود';
            $highlights[] = array(
                'id' => 'moon-interference',
                'title' => 'وضعیت ماه',
                'value' => trim((isset($moon['phaseLabelFa']) ? $moon['phaseLabelFa'] : 'ماه') . '، ' . $illumination, '، '),
                'summary' => 'اثر نور ماه از قرارداد رصدخانه زنده گرفته شده است.',
                'source' => 'Astronomical calculation',
                'confidence' => 'medium',
            );
        }

        $visible = isset($data['visibleHighlights']) && is_array($data['visibleHighlights']) ? $data['visibleHighlights'] : array();
        foreach ($visible as $index => $item) {
            if (count($highlights) >= 3) {
                break;
            }
            $highlights[] = array(
                'id' => 'visible-' . (int) $index,
                'title' => isset($item['title']) ? $item['title'] : 'هایلایت امشب',
                'value' => isset($item['time']) ? $item['time'] : 'امشب',
                'summary' => isset($item['detail']) ? $item['detail'] : '',
                'source' => 'Astronomical calculation',
                'confidence' => 'medium',
            );
        }

        return $highlights;
    }

    private static function calculate_payload($timestamp)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return new WP_Error('jazireh_sky_invalid_time', 'Sky calculation received an invalid timestamp.', array('status' => 500));
        }

        $location = self::location();
        $timezone = new DateTimeZone($location['timezone']);
        $local_now = new DateTime('@' . $timestamp);
        $local_now->setTimezone($timezone);
        $date = $local_now->format('Y-m-d');

        $sunrise = self::solar_event($date, $location, $timezone, true, self::ZENITH_OFFICIAL);
        $sunset = self::solar_event($date, $location, $timezone, false, self::ZENITH_OFFICIAL);
        $astro_dusk = self::solar_event($date, $location, $timezone, false, self::ZENITH_ASTRONOMICAL);
        $next_sunrise = self::solar_event(gmdate('Y-m-d', strtotime($date . ' +1 day')), $location, $timezone, true, self::ZENITH_OFFICIAL);

        if (is_wp_error($sunrise) || is_wp_error($sunset) || is_wp_error($astro_dusk) || is_wp_error($next_sunrise)) {
            return new WP_Error('jazireh_sky_solar_time_failed', 'Could not calculate solar times for Tehran.', array('status' => 500));
        }

        $moon_widget = Jazireh_Moon_Service::widget();
        $moon = is_array($moon_widget) && !empty($moon_widget['data']) && is_array($moon_widget['data']) ? $moon_widget['data'] : array();
        if (!$moon) {
            return new WP_Error('jazireh_sky_moon_failed', 'Could not calculate moon status for the sky widget.', array('status' => 500));
        }

        return array(
            'location' => $location,
            'calculatedAt' => gmdate(DATE_ATOM, $timestamp),
            'sunrise' => self::time_payload($sunrise),
            'sunset' => self::time_payload($sunset),
            'moonStatus' => array(
                'phaseName' => $moon['phaseName'],
                'phaseLabelFa' => $moon['phaseLabelFa'],
                'illuminationPercent' => $moon['illuminationPercent'],
                'waxingWaning' => $moon['waxingWaning'],
                'waxingWaningLabelFa' => $moon['waxingWaningLabelFa'],
                'moonAgeDays' => $moon['moonAgeDays'],
            ),
            'moonPhaseReference' => array(
                'nextFullMoon' => $moon['nextFullMoon'],
                'nextNewMoon' => $moon['nextNewMoon'],
            ),
            'bestObservationWindow' => self::observation_window($astro_dusk, $next_sunrise),
            'visibleHighlights' => self::visible_highlights((int) $local_now->format('n'), $moon),
            'conditionLabelFa' => 'محاسبه نجومی برای آسمان امشب',
            'observationScore' => self::observation_score($moon),
            'method' => 'Solar time calculation with lunar phase reference for Tehran.',
        );
    }

    private static function solar_event($date, $location, DateTimeZone $timezone, $sunrise, $zenith)
    {
        $date_time = new DateTime($date . ' 12:00:00', $timezone);
        $day_of_year = (int) $date_time->format('z') + 1;
        $longitude_hour = $location['longitude'] / 15;
        $approx_time = $day_of_year + (($sunrise ? 6 : 18) - $longitude_hour) / 24;
        $mean_anomaly = (0.9856 * $approx_time) - 3.289;
        $true_longitude = $mean_anomaly + (1.916 * sin(deg2rad($mean_anomaly))) + (0.020 * sin(deg2rad(2 * $mean_anomaly))) + 282.634;
        $true_longitude = self::normalize_degrees($true_longitude);
        $right_ascension = rad2deg(atan(0.91764 * tan(deg2rad($true_longitude))));
        $right_ascension = self::normalize_degrees($right_ascension);
        $right_ascension += floor($true_longitude / 90) * 90 - floor($right_ascension / 90) * 90;
        $right_ascension /= 15;

        $sin_declination = 0.39782 * sin(deg2rad($true_longitude));
        $cos_declination = cos(asin($sin_declination));
        $cos_hour_angle = (cos(deg2rad($zenith)) - ($sin_declination * sin(deg2rad($location['latitude'])))) / ($cos_declination * cos(deg2rad($location['latitude'])));

        if ($cos_hour_angle < -1 || $cos_hour_angle > 1) {
            return new WP_Error('jazireh_sky_solar_event_unavailable', 'Solar event is unavailable for this date/location.', array('status' => 500));
        }

        $hour_angle = $sunrise ? 360 - rad2deg(acos($cos_hour_angle)) : rad2deg(acos($cos_hour_angle));
        $hour_angle /= 15;
        $local_mean_time = $hour_angle + $right_ascension - (0.06571 * $approx_time) - 6.622;
        $utc_hour = fmod($local_mean_time - $longitude_hour, 24);
        if ($utc_hour < 0) {
            $utc_hour += 24;
        }

        $event_utc = new DateTime($date . ' 00:00:00', new DateTimeZone('UTC'));
        $event_utc->modify('+' . (int) round($utc_hour * 3600) . ' seconds');
        $event_local = clone $event_utc;
        $event_local->setTimezone($timezone);

        return array('utc' => $event_utc, 'local' => $event_local);
    }

    private static function observation_window($astro_dusk, $next_sunrise)
    {
        $start = clone $astro_dusk['local'];
        $end = clone $next_sunrise['local'];
        $end->modify('-90 minutes');

        return array(
            'start' => $start->format(DATE_ATOM),
            'end' => $end->format(DATE_ATOM),
            'labelFa' => self::format_local_time($start) . ' تا ' . self::format_local_time($end),
            'basis' => 'از تاریکی نجومی تا پیش از روشنایی بامداد',
        );
    }

    private static function visible_highlights($month, $moon)
    {
        $seasonal = array(
            12 => array('title' => 'صورت فلکی جبار', 'detail' => 'یکی از شاخص‌ترین الگوهای زمستانی آسمان شب'),
            1 => array('title' => 'صورت فلکی جبار', 'detail' => 'یکی از شاخص‌ترین الگوهای زمستانی آسمان شب'),
            2 => array('title' => 'ستاره شباهنگ', 'detail' => 'درخشان‌ترین ستاره آسمان شب در افق جنوبی'),
            3 => array('title' => 'صورت فلکی اسد', 'detail' => 'نشانه‌ای مناسب برای آسمان بهاری'),
            4 => array('title' => 'صورت فلکی اسد', 'detail' => 'نشانه‌ای مناسب برای آسمان بهاری'),
            5 => array('title' => 'سماک رامح', 'detail' => 'ستاره‌ای درخشان در آسمان بهاری و آغاز تابستان'),
            6 => array('title' => 'مثلث تابستانی', 'detail' => 'سه ستاره درخشان نسر واقع، نسر طائر و دنب'),
            7 => array('title' => 'راه شیری تابستانی', 'detail' => 'در آسمان تاریک، نوار راه شیری بهتر دیده می‌شود'),
            8 => array('title' => 'مثلث تابستانی', 'detail' => 'سه ستاره درخشان نسر واقع، نسر طائر و دنب'),
            9 => array('title' => 'صورت فلکی ذات‌الکرسی', 'detail' => 'الگوی W شکل در آسمان شمالی'),
            10 => array('title' => 'کهکشان آندرومدا', 'detail' => 'در آسمان تاریک با چشم غیرمسلح یا دوربین دوچشمی قابل جست‌وجو است'),
            11 => array('title' => 'خوشه پروین', 'detail' => 'خوشه‌ای روشن و مناسب برای رصد با چشم غیرمسلح'),
        );

        return array(
            array(
                'title' => 'ماه: ' . $moon['phaseLabelFa'],
                'time' => 'تمام شب',
                'detail' => 'روشنایی تقریبی ماه: ' . number_format_i18n((float) $moon['illuminationPercent'], 1) . '٪',
            ),
            array(
                'title' => $seasonal[$month]['title'],
                'time' => 'پس از تاریکی آسمان',
                'detail' => $seasonal[$month]['detail'],
            ),
            array(
                'title' => 'سیارات روشن',
                'time' => 'نزدیک غروب یا بامداد',
                'detail' => 'برای موقعیت دقیق سیارات، مرحله بعدی Sky Tonight باید محاسبات سیاره‌ای کامل اضافه کند.',
            ),
        );
    }

    private static function observation_score($moon)
    {
        $illumination = isset($moon['illuminationPercent']) ? (float) $moon['illuminationPercent'] : 50;
        $moon_penalty = min(3.5, $illumination / 30);
        $score = max(4, round(8.5 - $moon_penalty, 1));

        return array(
            'seeing' => $score,
            'transparency' => $score,
        );
    }

    private static function time_payload($event)
    {
        return array(
            'utc' => $event['utc']->format(DATE_ATOM),
            'local' => $event['local']->format(DATE_ATOM),
            'localTime' => self::format_local_time($event['local']),
        );
    }

    private static function format_local_time(DateTime $date_time)
    {
        return $date_time->format('H:i');
    }

    private static function normalize_degrees($value)
    {
        $value = fmod($value, 360);
        return $value < 0 ? $value + 360 : $value;
    }

    private static function location()
    {
        return array(
            'name' => 'تهران، ایران',
            'latitude' => 35.6892,
            'longitude' => 51.3890,
            'timezone' => 'Asia/Tehran',
        );
    }
}
