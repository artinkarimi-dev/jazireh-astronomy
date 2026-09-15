<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Widgets
{
    const STATE_READY = 'ready';
    const STATE_STALE = 'stale';
    const STATE_ERROR = 'error';

    const OPTION_PREFIX = 'jazireh_widget_last_good_';
    const STATUS_OPTION = 'jazireh_widget_statuses';

    public static function response($key, $state, $data = null, $args = array())
    {
        $now = current_time('timestamp', true);
        $updated_at = isset($args['updatedAt']) ? $args['updatedAt'] : gmdate(DATE_ATOM, $now);
        $expires_at = isset($args['expiresAt']) ? $args['expiresAt'] : null;

        return array(
            'key' => sanitize_key($key),
            'status' => self::normalize_state($state),
            'updatedAt' => self::normalize_date($updated_at),
            'expiresAt' => $expires_at ? self::normalize_date($expires_at) : null,
            'source' => isset($args['source']) ? sanitize_text_field((string) $args['source']) : '',
            'sourceUrl' => isset($args['sourceUrl']) ? esc_url_raw((string) $args['sourceUrl']) : '',
            'message' => isset($args['message']) ? sanitize_text_field((string) $args['message']) : '',
            'data' => $data,
        );
    }

    public static function ready($key, $data, $args = array())
    {
        $response = self::response($key, self::STATE_READY, $data, $args);
        self::store_last_good($key, $response);
        self::record_status($key, $response);
        return $response;
    }

    public static function stale($key, $data, $args = array())
    {
        $response = self::response($key, self::STATE_STALE, $data, $args);
        self::record_status($key, $response);
        return $response;
    }

    public static function error($key, $message, $args = array())
    {
        $last_good = self::last_good($key);
        if (is_array($last_good)) {
            $last_good['status'] = self::STATE_STALE;
            $last_good['message'] = sanitize_text_field($message);
            self::record_status($key, $last_good);
            return $last_good;
        }

        $response = self::response($key, self::STATE_ERROR, null, array_merge($args, array(
            'message' => $message,
        )));
        self::record_status($key, $response);
        return $response;
    }

    public static function last_good_as_stale($key, $message = '')
    {
        $last_good = self::last_good($key);
        if (!is_array($last_good)) {
            return null;
        }

        $last_good['status'] = self::STATE_STALE;
        $last_good['message'] = sanitize_text_field($message);
        $last_good['servedAtUtc'] = gmdate(DATE_ATOM);
        self::record_status($key, $last_good);
        return $last_good;
    }

    public static function cache_key($key)
    {
        return 'jazireh_widget_' . sanitize_key($key);
    }

    public static function get_cached($key)
    {
        $cached = get_transient(self::cache_key($key));
        return is_array($cached) ? $cached : null;
    }

    public static function cached_or_last_good($key, $message = '')
    {
        $cached = self::get_cached($key);
        if (is_array($cached) && !self::is_stale($cached)) {
            return $cached;
        }

        $last_good = self::last_good_as_stale($key, $message);
        if (is_array($last_good)) {
            return $last_good;
        }

        return null;
    }

    public static function set_cached($key, $response, $ttl)
    {
        $ttl = max(1, (int) $ttl);
        set_transient(self::cache_key($key), $response, $ttl);
        return $response;
    }

    public static function delete_cached($key)
    {
        delete_transient(self::cache_key($key));
    }

    public static function store_last_good($key, $response)
    {
        if (!is_array($response) || $response['status'] !== self::STATE_READY) {
            return;
        }
        update_option(self::last_good_option($key), $response, false);
    }

    public static function last_good($key)
    {
        $stored = get_option(self::last_good_option($key), null);
        return is_array($stored) ? $stored : null;
    }

    public static function is_stale($response)
    {
        if (!is_array($response) || empty($response['expiresAt'])) {
            return false;
        }
        $expires = strtotime((string) $response['expiresAt']);
        return $expires && $expires < current_time('timestamp', true);
    }

    public static function statuses()
    {
        $statuses = get_option(self::STATUS_OPTION, array());
        return is_array($statuses) ? $statuses : array();
    }

    public static function registry_payload()
    {
        return array(
            'contract' => array(
                'states' => array(self::STATE_READY, self::STATE_STALE, self::STATE_ERROR),
                'fields' => array('key', 'status', 'updatedAt', 'expiresAt', 'source', 'sourceUrl', 'message', 'data'),
            ),
            'widgets' => self::statuses(),
        );
    }

    private static function record_status($key, $response)
    {
        $statuses = self::statuses();
        $key = sanitize_key($key);
        $previous = isset($statuses[$key]) && is_array($statuses[$key]) ? $statuses[$key] : array();
        $last_success_at = isset($previous['lastSuccessAt']) ? $previous['lastSuccessAt'] : '';
        $last_error = isset($previous['lastError']) ? $previous['lastError'] : '';

        if ($response['status'] === self::STATE_READY) {
            $last_success_at = $response['updatedAt'];
            $last_error = '';
        } elseif ($response['status'] === self::STATE_ERROR || !empty($response['message'])) {
            $last_error = $response['message'];
        }

        $statuses[sanitize_key($key)] = array(
            'status' => $response['status'],
            'updatedAt' => $response['updatedAt'],
            'expiresAt' => $response['expiresAt'],
            'source' => $response['source'],
            'message' => $response['message'],
            'lastSuccessAt' => $last_success_at,
            'lastError' => $last_error,
        );
        update_option(self::STATUS_OPTION, $statuses, false);
    }

    private static function last_good_option($key)
    {
        return self::OPTION_PREFIX . sanitize_key($key);
    }

    private static function normalize_state($state)
    {
        $state = sanitize_key($state);
        return in_array($state, array(self::STATE_READY, self::STATE_STALE, self::STATE_ERROR), true) ? $state : self::STATE_ERROR;
    }

    private static function normalize_date($value)
    {
        if (is_numeric($value)) {
            return gmdate(DATE_ATOM, (int) $value);
        }

        $timestamp = strtotime((string) $value);
        return $timestamp ? gmdate(DATE_ATOM, $timestamp) : gmdate(DATE_ATOM, current_time('timestamp', true));
    }
}
