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
    const REFERENCE_SOURCE = 'USNO/NASA primary lunar phase instants, UTC';

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

        $state = self::phase_state($timestamp);
        if (is_wp_error($state)) {
            return $state;
        }

        return array(
            'scope' => 'global-geocentric',
            'timeBasis' => 'UTC',
            'calculatedAt' => gmdate(DATE_ATOM, $timestamp),
            'phaseName' => $state['phaseName'],
            'phaseLabelFa' => $state['phaseLabelFa'],
            'illuminationPercent' => round($state['illuminationPercent'], 1),
            'illuminationFraction' => round($state['illuminationPercent'] / 100, 5),
            'phaseAngleDegrees' => round($state['phaseAngleDegrees'], 3),
            'waxingWaning' => $state['waxingWaning'],
            'waxingWaningLabelFa' => $state['waxingWaningLabelFa'],
            'moonAgeDays' => round($state['moonAgeDays'], 2),
            'cyclePercent' => round($state['cyclePercent'], 1),
            'lunationLengthDays' => round($state['lunationLengthDays'], 5),
            'nextFullMoon' => $state['nextFullMoon'],
            'nextNewMoon' => $state['nextNewMoon'],
            'referenceSource' => $state['referenceSource'],
            'method' => $state['method'],
        );
    }

    private static function phase_state($timestamp)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return new WP_Error('jazireh_moon_invalid_time', 'Moon calculation received an invalid timestamp.', array('status' => 500));
        }

        $reference_state = self::reference_phase_state($timestamp);
        if (is_array($reference_state)) {
            return $reference_state;
        }

        $julian_day = self::julian_day($timestamp);
        $moon_age = self::moon_age($julian_day);
        $cycle_fraction = $moon_age / self::SYNODIC_MONTH;
        $phase_angle = $cycle_fraction * 360;
        $illumination = self::illumination_from_angle($phase_angle);
        $phase = self::phase_label_from_angle($phase_angle);
        $waxing = self::waxing_from_angle($phase_angle);

        return array(
            'phaseName' => $phase['name'],
            'phaseLabelFa' => $phase['fa'],
            'illuminationPercent' => $illumination,
            'phaseAngleDegrees' => $phase_angle,
            'waxingWaning' => $waxing ? 'waxing' : 'waning',
            'waxingWaningLabelFa' => $waxing ? 'افزاینده' : 'کاهنده',
            'moonAgeDays' => $moon_age,
            'cyclePercent' => $cycle_fraction * 100,
            'lunationLengthDays' => self::SYNODIC_MONTH,
            'nextFullMoon' => self::next_full_moon($timestamp, $moon_age),
            'nextNewMoon' => self::next_new_moon($timestamp, $moon_age),
            'referenceSource' => 'Mean synodic month fallback',
            'method' => 'Mean synodic month fallback anchored to a known new moon epoch.',
        );
    }

    private static function reference_phase_state($timestamp)
    {
        $events = self::reference_phase_events();
        $previous_new_index = null;
        $next_new_index = null;

        foreach ($events as $index => $event) {
            if ($event['phase'] === 'new_moon' && $event['timestamp'] <= $timestamp) {
                $previous_new_index = $index;
            }
            if ($event['phase'] === 'new_moon' && $event['timestamp'] > $timestamp) {
                $next_new_index = $index;
                break;
            }
        }

        if ($previous_new_index === null || $next_new_index === null) {
            return null;
        }

        $previous_new = $events[$previous_new_index];
        $next_new = $events[$next_new_index];
        $segment_events = array();
        for ($i = $previous_new_index; $i <= $next_new_index; $i++) {
            $segment_events[] = $events[$i];
        }

        $previous = $segment_events[0];
        $next = $segment_events[count($segment_events) - 1];
        foreach ($segment_events as $index => $event) {
            if ($event['timestamp'] <= $timestamp) {
                $previous = $event;
            }
            if ($event['timestamp'] >= $timestamp) {
                $next = $event;
                break;
            }
        }

        $angle_start = self::phase_angle_for_event($previous['phase']);
        $angle_end = self::phase_angle_for_event($next['phase']);
        if ($next['phase'] === 'new_moon' && $previous['phase'] !== 'new_moon') {
            $angle_end = 360;
        }

        $duration = max(1, $next['timestamp'] - $previous['timestamp']);
        $progress = min(1, max(0, ($timestamp - $previous['timestamp']) / $duration));
        $phase_angle = $angle_start + (($angle_end - $angle_start) * $progress);
        $phase_angle = self::normalize_degrees($phase_angle);
        $moon_age = ($timestamp - $previous_new['timestamp']) / DAY_IN_SECONDS;
        $lunation_length = ($next_new['timestamp'] - $previous_new['timestamp']) / DAY_IN_SECONDS;
        $phase = self::phase_label_from_angle($phase_angle);
        $waxing = self::waxing_from_angle($phase_angle);

        return array(
            'phaseName' => $phase['name'],
            'phaseLabelFa' => $phase['fa'],
            'illuminationPercent' => self::illumination_from_angle($phase_angle),
            'phaseAngleDegrees' => $phase_angle,
            'waxingWaning' => $waxing ? 'waxing' : 'waning',
            'waxingWaningLabelFa' => $waxing ? 'افزاینده' : 'کاهنده',
            'moonAgeDays' => max(0, min($lunation_length, $moon_age)),
            'cyclePercent' => max(0, min(100, ($moon_age / $lunation_length) * 100)),
            'lunationLengthDays' => $lunation_length,
            'nextFullMoon' => self::next_reference_phase($events, $timestamp, 'full_moon'),
            'nextNewMoon' => gmdate(DATE_ATOM, $next_new['timestamp']),
            'referenceSource' => self::REFERENCE_SOURCE,
            'method' => 'Piecewise interpolation between authoritative primary lunar phase instants.',
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

    private static function phase_label_from_angle($phase_angle)
    {
        $angle = self::normalize_degrees($phase_angle);
        if ($angle < 22.5 || $angle >= 337.5) {
            return array('name' => 'new_moon', 'fa' => 'ماه نو');
        }
        if ($angle < 67.5) {
            return array('name' => 'waxing_crescent', 'fa' => 'هلال افزاینده');
        }
        if ($angle < 112.5) {
            return array('name' => 'first_quarter', 'fa' => 'تربیع اول');
        }
        if ($angle < 157.5) {
            return array('name' => 'waxing_gibbous', 'fa' => 'کوژ افزاینده');
        }
        if ($angle < 202.5) {
            return array('name' => 'full_moon', 'fa' => 'ماه کامل');
        }
        if ($angle < 247.5) {
            return array('name' => 'waning_gibbous', 'fa' => 'کوژ کاهنده');
        }
        if ($angle < 292.5) {
            return array('name' => 'last_quarter', 'fa' => 'تربیع آخر');
        }
        return array('name' => 'waning_crescent', 'fa' => 'هلال کاهنده');
    }

    private static function illumination_from_angle($phase_angle)
    {
        $illumination = (1 - cos(deg2rad(self::normalize_degrees($phase_angle)))) / 2 * 100;
        return max(0, min(100, $illumination));
    }

    private static function waxing_from_angle($phase_angle)
    {
        $angle = self::normalize_degrees($phase_angle);
        return $angle < 180;
    }

    private static function phase_angle_for_event($phase)
    {
        switch ($phase) {
            case 'first_quarter':
                return 90;
            case 'full_moon':
                return 180;
            case 'last_quarter':
                return 270;
            case 'new_moon':
            default:
                return 0;
        }
    }

    private static function next_reference_phase($events, $timestamp, $phase)
    {
        foreach ($events as $event) {
            if ($event['phase'] === $phase && $event['timestamp'] > $timestamp) {
                return gmdate(DATE_ATOM, $event['timestamp']);
            }
        }
        return '';
    }

    private static function normalize_degrees($value)
    {
        $normalized = fmod((float) $value, 360);
        return $normalized < 0 ? $normalized + 360 : $normalized;
    }

    private static function reference_phase_events()
    {
        $items = array(
            array('2026-08-12T17:37:00Z', 'new_moon'),
            array('2026-08-20T02:46:00Z', 'first_quarter'),
            array('2026-08-28T04:18:00Z', 'full_moon'),
            array('2026-09-04T07:51:00Z', 'last_quarter'),
            array('2026-09-11T03:27:00Z', 'new_moon'),
            array('2026-09-18T20:44:00Z', 'first_quarter'),
            array('2026-09-26T16:49:00Z', 'full_moon'),
            array('2026-10-03T13:25:00Z', 'last_quarter'),
            array('2026-10-10T15:50:00Z', 'new_moon'),
            array('2026-10-18T16:12:00Z', 'first_quarter'),
            array('2026-10-26T04:12:00Z', 'full_moon'),
            array('2026-11-01T20:28:00Z', 'last_quarter'),
            array('2026-11-09T07:02:00Z', 'new_moon'),
            array('2026-11-17T11:48:00Z', 'first_quarter'),
            array('2026-11-24T14:53:00Z', 'full_moon'),
            array('2026-12-01T06:08:00Z', 'last_quarter'),
            array('2026-12-09T00:52:00Z', 'new_moon'),
            array('2026-12-17T05:42:00Z', 'first_quarter'),
            array('2026-12-24T01:28:00Z', 'full_moon'),
            array('2026-12-30T18:59:00Z', 'last_quarter'),
        );

        return array_map(function ($item) {
            return array(
                'timestamp' => strtotime($item[0]),
                'phase' => $item[1],
                'iso' => $item[0],
            );
        }, $items);
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
