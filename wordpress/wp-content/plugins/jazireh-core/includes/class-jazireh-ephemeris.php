<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Ephemeris
{
    private const DEFAULT_CITY = 'تهران، ایران';
    private const DEFAULT_LATITUDE = 35.6892;
    private const DEFAULT_LONGITUDE = 51.3890;

    public static function object($object, $observer = array(), $timestamp = null, $mode = 'observer')
    {
        $timestamp = $timestamp ?: current_time('timestamp', true);
        $observer = self::normalize_observer($observer);
        $object = sanitize_key((string) $object);
        $mode = sanitize_key((string) $mode) ?: 'observer';

        $cached = Jazireh_Astronomy_Cache::get($object, $observer, $mode, $timestamp, self::bucket_minutes($mode));
        if (is_array($cached)) {
            return $cached;
        }

        $provider = new Jazireh_Horizons_Provider();
        $payload = $provider->object($object, $observer, $timestamp, $mode);
        if (!empty($payload['status']) && $payload['status'] === 'unavailable') {
            $last_good = Jazireh_Astronomy_Cache::last_good($object, $observer, $mode);
            if (is_array($last_good)) {
                return $last_good;
            }
            return Jazireh_Astronomy_Cache::set($object, $observer, $mode, $timestamp, $payload, 10 * MINUTE_IN_SECONDS, self::bucket_minutes($mode));
        }

        return Jazireh_Astronomy_Cache::set($object, $observer, $mode, $timestamp, $payload, self::ttl($mode), self::bucket_minutes($mode));
    }

    public static function planets($observer = array(), $timestamp = null)
    {
        $objects = array('mercury', 'venus', 'mars', 'jupiter', 'saturn');
        $items = array();
        foreach ($objects as $object) {
            $items[] = self::object($object, $observer, $timestamp, 'planet-current');
        }
        return $items;
    }

    public static function contract($args)
    {
        $timestamp = isset($args['timestamp']) ? (int) $args['timestamp'] : current_time('timestamp', true);
        $observer = self::normalize_observer($args['observer'] ?? array());

        return array(
            'status' => 'ready',
            'object' => sanitize_key((string) ($args['object'] ?? 'unknown')),
            'type' => sanitize_key((string) ($args['type'] ?? 'solar-system-object')),
            'observer' => $observer,
            'time' => array(
                'calculatedAt' => wp_date(DATE_ATOM, $timestamp),
                'generatedAtUtc' => gmdate(DATE_ATOM, $timestamp),
                'validForMinutes' => self::bucket_minutes((string) ($args['method'] ?? 'observer')),
            ),
            'source' => sanitize_text_field((string) ($args['source'] ?? 'unknown')),
            'method' => sanitize_text_field((string) ($args['method'] ?? 'observer-ephemeris')),
            'accuracy' => sanitize_key((string) ($args['accuracy'] ?? 'unknown')),
            'confidence' => sanitize_key((string) ($args['confidence'] ?? 'medium')),
            'position' => self::position($args['position'] ?? array()),
            'appearance' => self::appearance($args['appearance'] ?? array()),
            'riseSetTransit' => self::rise_set_transit($args['riseSetTransit'] ?? array()),
            'visibility' => Jazireh_Visibility_Service::from_ephemeris($args),
            'fallback' => array(
                'isFallback' => false,
                'fallbackReason' => '',
                'displayWarning' => '',
            ),
            'raw' => isset($args['raw']) && is_array($args['raw']) ? $args['raw'] : array(),
        );
    }

    public static function unavailable_contract($object, $observer, $timestamp, $reason, $source = 'unknown')
    {
        $observer = self::normalize_observer($observer);
        return array(
            'status' => 'unavailable',
            'object' => sanitize_key((string) $object),
            'type' => 'solar-system-object',
            'observer' => $observer,
            'time' => array(
                'calculatedAt' => wp_date(DATE_ATOM, $timestamp),
                'generatedAtUtc' => gmdate(DATE_ATOM, $timestamp),
                'validForMinutes' => 10,
            ),
            'source' => sanitize_text_field((string) $source),
            'method' => 'observer-ephemeris',
            'accuracy' => 'unavailable',
            'confidence' => 'low',
            'position' => self::position(array()),
            'appearance' => self::appearance(array()),
            'riseSetTransit' => self::rise_set_transit(array('status' => 'unavailable')),
            'visibility' => array(
                'status' => 'unavailable',
                'label' => 'داده موجود نیست',
                'score' => null,
                'reason' => 'داده معتبر نجومی در دسترس نیست.',
                'bestWindow' => null,
            ),
            'fallback' => array(
                'isFallback' => true,
                'fallbackReason' => sanitize_text_field((string) $reason),
                'displayWarning' => 'داده معتبر نجومی فعلا در دسترس نیست.',
            ),
            'raw' => array(),
        );
    }

    public static function normalize_observer($observer = array())
    {
        $settings = get_option(Jazireh_Settings::OPTION_NAME, array());
        $city = self::value($observer, 'city', self::value($settings, 'default_city', self::DEFAULT_CITY));
        $latitude = (float) self::value($observer, 'latitude', self::value($settings, 'default_latitude', self::DEFAULT_LATITUDE));
        $longitude = (float) self::value($observer, 'longitude', self::value($settings, 'default_longitude', self::DEFAULT_LONGITUDE));

        return array(
            'city' => $city === 'تهران' ? self::DEFAULT_CITY : sanitize_text_field((string) $city),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'elevationMeters' => isset($observer['elevationMeters']) && $observer['elevationMeters'] !== '' ? (float) $observer['elevationMeters'] : null,
            'timezone' => sanitize_text_field((string) self::value($observer, 'timezone', wp_timezone_string())),
        );
    }

    private static function position($position)
    {
        return array(
            'rightAscension' => $position['rightAscension'] ?? null,
            'declination' => $position['declination'] ?? null,
            'altitude' => isset($position['altitude']) ? (float) $position['altitude'] : null,
            'azimuth' => isset($position['azimuth']) ? (float) $position['azimuth'] : null,
            'distanceAu' => isset($position['distanceAu']) ? (float) $position['distanceAu'] : null,
        );
    }

    private static function appearance($appearance)
    {
        return array(
            'magnitude' => isset($appearance['magnitude']) ? (float) $appearance['magnitude'] : null,
            'elongation' => isset($appearance['elongation']) ? (float) $appearance['elongation'] : null,
            'illumination' => isset($appearance['illumination']) ? (float) $appearance['illumination'] : null,
            'phaseAngle' => isset($appearance['phaseAngle']) ? (float) $appearance['phaseAngle'] : null,
            'angularDiameter' => isset($appearance['angularDiameter']) ? (float) $appearance['angularDiameter'] : null,
        );
    }

    private static function rise_set_transit($values)
    {
        return array(
            'rise' => $values['rise'] ?? null,
            'set' => $values['set'] ?? null,
            'transit' => $values['transit'] ?? null,
            'status' => sanitize_key((string) ($values['status'] ?? 'not-calculated')),
        );
    }

    private static function ttl($mode)
    {
        return $mode === 'planet-current' ? 45 * MINUTE_IN_SECONDS : HOUR_IN_SECONDS;
    }

    private static function bucket_minutes($mode)
    {
        return $mode === 'planet-current' ? 30 : 60;
    }

    private static function value($array, $key, $default)
    {
        return is_array($array) && array_key_exists($key, $array) && $array[$key] !== '' ? $array[$key] : $default;
    }
}
