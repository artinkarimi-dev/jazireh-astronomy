<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Moon_Service
{
    const WIDGET_KEY = 'moon';
    const CACHE_TTL = 900;
    const SYNODIC_MONTH = 29.530588853;
    const KNOWN_NEW_MOON_JD = 2451550.25972;

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

    private static function calculate_payload($timestamp)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return new WP_Error('jazireh_moon_invalid_time', 'Moon calculation received an invalid timestamp.', array('status' => 500));
        }

        $julian_day = self::julian_day($timestamp);
        $moon_age = self::moon_age($julian_day);
        $cycle_fraction = $moon_age / self::SYNODIC_MONTH;
        $illumination = (1 - cos(2 * M_PI * $cycle_fraction)) / 2 * 100;
        $waxing = $moon_age < (self::SYNODIC_MONTH / 2);
        $phase = self::phase_label($moon_age);

        return array(
            'location' => self::default_location(),
            'calculatedAt' => gmdate(DATE_ATOM, $timestamp),
            'phaseName' => $phase['name'],
            'phaseLabelFa' => $phase['fa'],
            'illuminationPercent' => round($illumination, 1),
            'waxingWaning' => $waxing ? 'waxing' : 'waning',
            'waxingWaningLabelFa' => $waxing ? 'افزایشی' : 'کاهشی',
            'moonAgeDays' => round($moon_age, 2),
            'cyclePercent' => round($cycle_fraction * 100, 1),
            'nextFullMoon' => self::next_full_moon($timestamp, $moon_age),
            'nextNewMoon' => self::next_new_moon($timestamp, $moon_age),
            'method' => 'Synodic month calculation anchored to a known new moon epoch.',
        );
    }

    private static function julian_day($timestamp)
    {
        return ((float) $timestamp / 86400) + 2440587.5;
    }

    private static function moon_age($julian_day)
    {
        $age = fmod($julian_day - self::KNOWN_NEW_MOON_JD, self::SYNODIC_MONTH);
        if ($age < 0) {
            $age += self::SYNODIC_MONTH;
        }
        return $age;
    }

    private static function next_full_moon($timestamp, $moon_age)
    {
        $half_month = self::SYNODIC_MONTH / 2;
        $days_until = $moon_age < $half_month
            ? $half_month - $moon_age
            : self::SYNODIC_MONTH + $half_month - $moon_age;

        return gmdate(DATE_ATOM, $timestamp + (int) round($days_until * DAY_IN_SECONDS));
    }

    private static function next_new_moon($timestamp, $moon_age)
    {
        $days_until = self::SYNODIC_MONTH - $moon_age;
        if ($days_until <= 0.0001) {
            $days_until = self::SYNODIC_MONTH;
        }

        return gmdate(DATE_ATOM, $timestamp + (int) round($days_until * DAY_IN_SECONDS));
    }

    private static function phase_label($moon_age)
    {
        $phases = array(
            array(1.84566, 'new_moon', 'ماه نو'),
            array(5.53699, 'waxing_crescent', 'هلال افزایشی'),
            array(9.22831, 'first_quarter', 'تربیع اول'),
            array(12.91963, 'waxing_gibbous', 'کوژ افزایشی'),
            array(16.61096, 'full_moon', 'ماه کامل'),
            array(20.30228, 'waning_gibbous', 'کوژ کاهشی'),
            array(23.99361, 'last_quarter', 'تربیع آخر'),
            array(27.68493, 'waning_crescent', 'هلال کاهشی'),
            array(self::SYNODIC_MONTH, 'new_moon', 'ماه نو'),
        );

        foreach ($phases as $phase) {
            if ($moon_age < $phase[0]) {
                return array('name' => $phase[1], 'fa' => $phase[2]);
            }
        }

        return array('name' => 'new_moon', 'fa' => 'ماه نو');
    }

    private static function default_location()
    {
        return array(
            'name' => 'تهران، ایران',
            'latitude' => 35.6892,
            'longitude' => 51.3890,
            'timezone' => 'Asia/Tehran',
        );
    }
}
