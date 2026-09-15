<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Astronomy_Cache
{
    private const PREFIX = 'jazireh_ephemeris_';
    private const DEFAULT_TTL = 30 * MINUTE_IN_SECONDS;
    private const STALE_TTL = 2 * DAY_IN_SECONDS;

    public static function get($object, $observer, $mode, $timestamp, $bucket_minutes = 60)
    {
        $key = self::key($object, $observer, $mode, $timestamp, $bucket_minutes);
        $cached = get_transient($key);
        return is_array($cached) ? $cached : null;
    }

    public static function set($object, $observer, $mode, $timestamp, $payload, $ttl = self::DEFAULT_TTL, $bucket_minutes = 60)
    {
        $key = self::key($object, $observer, $mode, $timestamp, $bucket_minutes);
        set_transient($key, $payload, max(60, (int) $ttl));

        if (is_array($payload) && empty($payload['fallback']['isFallback'])) {
            set_transient(self::last_good_key($object, $observer, $mode), $payload, self::STALE_TTL);
        }

        return $payload;
    }

    public static function last_good($object, $observer, $mode)
    {
        $cached = get_transient(self::last_good_key($object, $observer, $mode));
        if (!is_array($cached)) {
            return null;
        }

        $cached['status'] = 'stale';
        $cached['fallback'] = array(
            'isFallback' => true,
            'fallbackReason' => 'authoritative ephemeris unavailable; last valid result returned',
            'displayWarning' => 'آخرین داده معتبر نمایش داده می‌شود.',
        );
        $cached['confidence'] = 'low';
        return $cached;
    }

    public static function key($object, $observer, $mode, $timestamp, $bucket_minutes = 60)
    {
        $bucket = self::time_bucket($timestamp, $bucket_minutes);
        $parts = array(
            sanitize_key((string) $object),
            self::coord($observer['latitude'] ?? null),
            self::coord($observer['longitude'] ?? null),
            sanitize_key((string) ($observer['timezone'] ?? wp_timezone_string())),
            sanitize_key((string) $mode),
            $bucket,
        );

        return self::PREFIX . md5(implode('|', $parts));
    }

    private static function last_good_key($object, $observer, $mode)
    {
        $parts = array(
            'last_good',
            sanitize_key((string) $object),
            self::coord($observer['latitude'] ?? null),
            self::coord($observer['longitude'] ?? null),
            sanitize_key((string) ($observer['timezone'] ?? wp_timezone_string())),
            sanitize_key((string) $mode),
        );

        return self::PREFIX . md5(implode('|', $parts));
    }

    private static function time_bucket($timestamp, $bucket_minutes)
    {
        $bucket_seconds = max(60, (int) $bucket_minutes * MINUTE_IN_SECONDS);
        return (string) (floor(((int) $timestamp) / $bucket_seconds) * $bucket_seconds);
    }

    private static function coord($value)
    {
        return number_format((float) $value, 4, '.', '');
    }
}
