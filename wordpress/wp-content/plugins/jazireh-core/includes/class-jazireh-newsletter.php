<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Newsletter
{
    const TABLE_SUFFIX = 'jazireh_newsletter_subscribers';
    const SCHEMA_VERSION = '1';

    public static function boot()
    {
        self::maybe_upgrade_schema();
    }

    public static function table_name()
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_SUFFIX;
    }

    public static function maybe_upgrade_schema()
    {
        if (get_option('jazireh_newsletter_schema_version') === self::SCHEMA_VERSION) {
            return;
        }

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $table = self::table_name();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(190) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email)
        ) {$charset};";

        dbDelta($sql);
        update_option('jazireh_newsletter_schema_version', self::SCHEMA_VERSION, false);
    }

    public static function subscribe($payload)
    {
        if (!is_array($payload)) {
            return new WP_Error('jazireh_newsletter_invalid_payload', 'درخواست نامعتبر است.', array('status' => 400));
        }

        $email = self::normalize_email($payload['email'] ?? '');
        if ($email === '') {
            return new WP_Error('jazireh_newsletter_required_email', 'ایمیل معتبر وارد کنید.', array(
                'status' => 422,
                'errors' => array('email' => 'این فیلد الزامی است.'),
            ));
        }
        if (strlen($email) > 190 || !is_email($email)) {
            return new WP_Error('jazireh_newsletter_invalid_email', 'ایمیل معتبر وارد کنید.', array('status' => 422));
        }

        $existing = self::find_by_email($email);
        $result = self::upsert($email, 'active');
        if ($result === false) {
            return new WP_Error('jazireh_newsletter_store_failed', 'ثبت عضویت با خطا روبه‌رو شد.', array('status' => 500));
        }

        return array(
            'status' => $existing ? 200 : 201,
            'message' => 'عضویت شما ثبت شد.',
        );
    }

    public static function count()
    {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM " . self::table_name());
    }

    private static function upsert($email, $status, $created_at = null, $updated_at = null)
    {
        global $wpdb;
        $table = self::table_name();
        $existing = self::find_by_email($email);

        $status = $status === 'unsubscribed' ? 'unsubscribed' : 'active';
        $created_at = self::normalize_datetime($created_at);
        $updated_at = self::normalize_datetime($updated_at ?: $created_at);

        if ($existing) {
            $result = $wpdb->update(
                $table,
                array(
                    'status' => $status,
                    'updated_at' => $updated_at ?: current_time('mysql', 1),
                ),
                array('id' => (int) $existing['id']),
                array('%s', '%s'),
                array('%d')
            );
            return $result === false ? false : 'updated';
        }

        $insert = $wpdb->insert(
            $table,
            array(
                'email' => $email,
                'status' => $status,
                'created_at' => $created_at ?: current_time('mysql', 1),
                'updated_at' => $updated_at ?: current_time('mysql', 1),
            ),
            array('%s', '%s', '%s', '%s')
        );
        return $insert === false ? false : 'created';
    }

    private static function find_by_email($email)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT id, email, status FROM " . self::table_name() . " WHERE email = %s LIMIT 1", $email), ARRAY_A);
    }

    private static function normalize_email($email)
    {
        return strtolower(trim(sanitize_email((string) $email)));
    }

    private static function normalize_datetime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $timestamp = strtotime($value);
        return $timestamp ? gmdate('Y-m-d H:i:s', $timestamp) : '';
    }
}
