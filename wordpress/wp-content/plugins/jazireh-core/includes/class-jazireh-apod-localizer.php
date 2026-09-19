<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_APOD_Localizer
{
    const CRON_HOOK = 'jazireh_apod_auto_localize';
    const OPTION_MONITOR = 'jazireh_apod_localizer_monitor';
    const LOCK_PREFIX = 'jazireh_apod_localizer_lock_';
    const RETRY_AFTER = 6 * HOUR_IN_SECONDS;

    public static function boot()
    {
        add_action(self::CRON_HOOK, array(__CLASS__, 'process_date'), 10, 1);
    }

    public static function maybe_queue_latest(array $items)
    {
        if (empty($items[0]) || !is_array($items[0])) {
            return;
        }

        $item = $items[0];
        $date = self::sanitize_date($item['date'] ?? '');
        if (!$date || !class_exists('Jazireh_APOD_Editorial')) {
            return;
        }

        Jazireh_APOD_Editorial::remember_source_item($item);
        $source_hash = Jazireh_APOD_Editorial::source_hash($item);
        Jazireh_APOD_Editorial::ensure_pending($item);

        if (!self::is_enabled()) {
            self::record_monitor($date, 'manual_pending', '');
            return;
        }

        if (Jazireh_APOD_Editorial::has_usable_for_date($date, $source_hash) || !Jazireh_APOD_Editorial::is_retry_allowed($date, self::RETRY_AFTER)) {
            return;
        }

        self::queue_date($date);
    }

    public static function queue_date($date)
    {
        $date = self::sanitize_date($date);
        if (!$date || wp_next_scheduled(self::CRON_HOOK, array($date))) {
            return false;
        }
        return (bool) wp_schedule_single_event(time() + MINUTE_IN_SECONDS, self::CRON_HOOK, array($date));
    }

    public static function clear_schedule()
    {
        wp_unschedule_hook(self::CRON_HOOK);
    }

    public static function process_date($date)
    {
        $date = self::sanitize_date($date);
        if (!$date || !self::is_enabled() || !class_exists('Jazireh_APOD_Editorial')) {
            return false;
        }
        wp_clear_scheduled_hook(self::CRON_HOOK, array($date));
        $item = Jazireh_APOD_Editorial::source_item_for_date($date);
        $source_hash = $item ? Jazireh_APOD_Editorial::source_hash($item) : '';
        if ($source_hash && Jazireh_APOD_Editorial::has_usable_for_date($date, $source_hash)) {
            self::record_monitor($date, 'ready', '');
            return true;
        }
        if (get_transient(self::LOCK_PREFIX . $date)) {
            return false;
        }

        set_transient(self::LOCK_PREFIX . $date, 1, 15 * MINUTE_IN_SECONDS);
        if (!$item) {
            self::record_failure($date, 'source_unavailable');
            delete_transient(self::LOCK_PREFIX . $date);
            return false;
        }

        $post_id = Jazireh_APOD_Editorial::ensure_pending($item);
        if (!$post_id) {
            self::record_failure($date, 'storage_unavailable');
            delete_transient(self::LOCK_PREFIX . $date);
            return false;
        }

        Jazireh_APOD_Editorial::record_attempt($post_id);
        $result = self::generate($item);
        if (is_wp_error($result)) {
            Jazireh_APOD_Editorial::mark_failed($post_id, self::public_error_code($result));
            self::record_failure($date, self::public_error_code($result));
            delete_transient(self::LOCK_PREFIX . $date);
            return false;
        }

        Jazireh_APOD_Editorial::mark_auto_ready($post_id, $result);
        self::record_monitor($date, 'auto_ready', '');
        delete_transient(self::LOCK_PREFIX . $date);
        return true;
    }

    public static function regenerate($post_id, $force = false)
    {
        $post_id = (int) $post_id;
        if (!$post_id || get_post_type($post_id) !== Jazireh_APOD_Editorial::POST_TYPE) {
            return new WP_Error('jazireh_apod_bad_post', 'APOD editorial entry was not found.');
        }
        $date = get_post_meta($post_id, Jazireh_APOD_Editorial::META_DATE, true);
        $status = get_post_meta($post_id, Jazireh_APOD_Editorial::META_STATUS, true);
        if (!$force && $status === Jazireh_APOD_Editorial::STATUS_MANUAL_READY) {
            return new WP_Error('jazireh_apod_manual_locked', 'Manual APOD content is protected.');
        }
        $item = Jazireh_APOD_Editorial::source_item_for_date($date);
        if (!$item) {
            return new WP_Error('jazireh_apod_source_missing', 'NASA APOD source item is unavailable.');
        }

        Jazireh_APOD_Editorial::record_attempt($post_id);
        $result = self::generate($item);
        if (is_wp_error($result)) {
            $error_code = self::public_error_code($result);
            Jazireh_APOD_Editorial::mark_failed($post_id, $error_code);
            self::record_monitor($date, 'failed', $error_code);
            return $result;
        }
        Jazireh_APOD_Editorial::mark_auto_ready($post_id, $result);
        self::record_monitor($date, 'auto_ready', '');
        return $result;
    }

    public static function provider_health()
    {
        if (!self::has_provider_key()) {
            return array(
                'status' => 'missing_key',
                'label' => 'کلید سرویس تنظیم نشده است',
                'badge' => 'error',
                'configured' => false,
            );
        }

        $monitor = self::monitor();
        $last_error = sanitize_key((string) ($monitor['lastError'] ?? ''));
        $status = sanitize_key((string) ($monitor['status'] ?? ''));

        if ($status === 'failed' && $last_error) {
            return array(
                'status' => $last_error,
                'label' => self::human_error_label($last_error),
                'badge' => 'error',
                'configured' => true,
            );
        }

        if (in_array($status, array('auto_ready', 'ready'), true)) {
            return array(
                'status' => 'active',
                'label' => 'فعال و سالم',
                'badge' => 'ready',
                'configured' => true,
            );
        }

        return array(
            'status' => 'configured',
            'label' => 'کلید تنظیم شده و آماده بررسی است',
            'badge' => 'not_checked',
            'configured' => true,
        );
    }

    public static function is_enabled()
    {
        $settings = get_option(Jazireh_Settings::OPTION_NAME, array());
        $enabled = isset($settings['integrations']['apod_auto_localization'])
            ? $settings['integrations']['apod_auto_localization'] === '1'
            : false;

        return (bool) apply_filters('jazireh_apod_localizer_enabled', $enabled);
    }

    public static function monitor()
    {
        $monitor = get_option(self::OPTION_MONITOR, array());
        return is_array($monitor) ? $monitor : array();
    }

    public static function human_error_label($code)
    {
        $code = sanitize_key((string) $code);
        $labels = array(
            'missing_key' => 'کلید سرویس تنظیم نشده است',
            'authentication_failed' => 'احراز هویت سرویس ناموفق بود',
            'insufficient_quota' => 'سهمیه یا صورتحساب سرویس کافی نیست',
            'rate_limited' => 'درخواست‌ها موقتاً بیش از حد مجاز شده‌اند',
            'model_unavailable' => 'مدل انتخاب‌شده در دسترس نیست',
            'invalid_provider_request' => 'درخواست ارسالی به سرویس معتبر نبود',
            'provider_timeout' => 'پاسخ سرویس به‌موقع دریافت نشد',
            'proxy_failure' => 'ارتباط سرویس از مسیر پراکسی برقرار نشد',
            'tls_failure' => 'ارتباط امن TLS/SSL با سرویس برقرار نشد',
            'network_failure' => 'اتصال شبکه به سرویس برقرار نشد',
            'invalid_provider_response' => 'پاسخ سرویس قابل استفاده نبود',
            'invalid_structured_output' => 'خروجی ساخت‌یافته سرویس معتبر نبود',
            'invalid_persian_output' => 'متن تولیدشده فارسی معتبر نبود',
            'unknown_provider_failure' => 'سرویس ترجمه با خطای ناشناخته متوقف شد',
            'source_unavailable' => 'داده منبع ناسا برای این تاریخ در دسترس نبود',
            'storage_unavailable' => 'ذخیره‌سازی ترجمه در وردپرس ناموفق بود',
        );

        return $labels[$code] ?? 'ترجمه خودکار انجام نشد';
    }

    private static function generate(array $item)
    {
        $payload = self::build_payload($item);
        $filtered = apply_filters('jazireh_apod_localizer_provider_response', null, $payload, $item);
        if (is_wp_error($filtered)) {
            return $filtered;
        }
        if (is_array($filtered)) {
            return self::validate_result_for_payload($filtered, $payload);
        }

        $key = self::provider_key();
        if (!$key) {
            return new WP_Error('jazireh_apod_provider_missing_key', 'Localization provider key is not configured.');
        }

        $request_body = wp_json_encode(self::openai_request_body($payload));
        if (!is_string($request_body) || $request_body === '') {
            return new WP_Error('invalid_provider_request', 'Localization provider request body could not be encoded.');
        }

        $response = wp_remote_post('https://api.openai.com/v1/responses', array(
            'timeout' => 45,
            'headers' => array(
                'Authorization' => 'Bearer ' . $key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ),
            'body' => $request_body,
        ));

        if (is_wp_error($response)) {
            return self::classify_wp_error($response);
        }

        $status = wp_remote_retrieve_response_code($response);
        $raw_body = wp_remote_retrieve_body($response);
        $body = json_decode($raw_body, true);
        if ($status < 200 || $status >= 300) {
            return self::classify_provider_response($status, $body, $raw_body);
        }
        if (!is_array($body)) {
            return new WP_Error('invalid_provider_response', 'Localization provider returned an unusable response.');
        }

        $text = self::extract_openai_text($body);
        if (!$text) {
            return new WP_Error('invalid_provider_response', 'Localization provider returned an empty response.');
        }

        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            return new WP_Error('invalid_structured_output', 'Localization provider returned invalid structured data.');
        }

        return self::validate_result_for_payload($decoded, $payload);
    }

    private static function build_payload(array $item)
    {
        return array(
            'date' => sanitize_text_field((string) ($item['date'] ?? '')),
            'titleOriginal' => sanitize_text_field((string) (($item['titleOriginal'] ?? '') ?: ($item['title'] ?? ''))),
            'contentOriginal' => wp_strip_all_tags((string) (($item['contentOriginal'] ?? '') ?: ($item['content'] ?? ''))),
            'mediaType' => sanitize_key((string) ($item['mediaType'] ?? 'image')),
            'photographer' => sanitize_text_field((string) ($item['photographer'] ?? 'NASA')),
            'sourceUrl' => esc_url_raw((string) ($item['sourceUrl'] ?? '')),
            'hdUrl' => esc_url_raw((string) ($item['hdUrl'] ?? '')),
            'serviceVersion' => sanitize_text_field((string) ($item['serviceVersion'] ?? '')),
            'sourceHash' => class_exists('Jazireh_APOD_Editorial') ? Jazireh_APOD_Editorial::source_hash($item) : '',
        );
    }

    private static function openai_request_body(array $payload)
    {
        return array(
            'model' => defined('JAZIREH_APOD_LOCALIZER_MODEL') ? JAZIREH_APOD_LOCALIZER_MODEL : 'gpt-5-mini',
            'input' => array(
                array(
                    'role' => 'developer',
                    'content' => array(array(
                        'type' => 'input_text',
                        'text' => 'You translate official NASA Astronomy Picture of the Day scientific content into professional Persian. Rules: translate faithfully; do not add facts; do not remove important scientific qualifications; preserve uncertainty words such as may, might, likely, possible, estimated; preserve all numbers, scientific units, dates, proper names, object identifiers, telescope/instrument names, URLs, source credit, and the exact sourceHash; do not sensationalize; do not add promotional language; write fluent natural Persian for a general scientific audience; return structured JSON only.',
                    )),
                ),
                array(
                    'role' => 'user',
                    'content' => array(array(
                        'type' => 'input_text',
                        'text' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    )),
                ),
            ),
            'text' => array(
                'format' => array(
                    'type' => 'json_schema',
                    'name' => 'jazireh_apod_persian_editorial',
                    'strict' => true,
                    'schema' => array(
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => array('titleFa', 'summaryFa', 'contentFa', 'translationNotes', 'sourceHash'),
                        'properties' => array(
                            'titleFa' => array('type' => 'string'),
                            'summaryFa' => array('type' => 'string'),
                            'contentFa' => array('type' => 'string'),
                            'translationNotes' => array('type' => 'array', 'items' => array('type' => 'string')),
                            'sourceHash' => array('type' => 'string'),
                        ),
                    ),
                ),
            ),
        );
    }

    private static function validate_result(array $result)
    {
        $title = sanitize_text_field((string) ($result['titleFa'] ?? ''));
        $summary = sanitize_textarea_field((string) ($result['summaryFa'] ?? ''));
        $content = sanitize_textarea_field((string) ($result['contentFa'] ?? ''));
        $source_hash = sanitize_text_field((string) ($result['sourceHash'] ?? ''));

        if (!$title || !$summary || !$content) {
            return new WP_Error('invalid_structured_output', 'Localization result is incomplete.');
        }
        if (!preg_match('/^[a-f0-9]{64}$/', strtolower($source_hash))) {
            return new WP_Error('invalid_structured_output', 'Localization result does not include a valid source hash.');
        }
        if (!self::contains_persian($title . ' ' . $summary . ' ' . $content)) {
            return new WP_Error('invalid_persian_output', 'Localization result does not contain Persian text.');
        }
        if (strlen($title) > 260 || strlen($summary) > 1600 || strlen($content) < 120 || strlen($content) > 12000) {
            return new WP_Error('invalid_structured_output', 'Localization result length is outside the accepted range.');
        }

        return array(
            'titleFa' => $title,
            'summaryFa' => $summary,
            'contentFa' => $content,
            'sourceHash' => strtolower($source_hash),
            'translationNotes' => array_values(array_filter(array_map('sanitize_text_field', is_array($result['translationNotes'] ?? null) ? $result['translationNotes'] : array()))),
        );
    }

    private static function validate_result_for_payload(array $result, array $payload)
    {
        $validated = self::validate_result($result);
        if (is_wp_error($validated)) {
            return $validated;
        }
        $expected_hash = strtolower((string) ($payload['sourceHash'] ?? ''));
        if (!$expected_hash || $validated['sourceHash'] !== $expected_hash) {
            return new WP_Error('invalid_structured_output', 'Localization result source hash does not match current NASA source.');
        }
        return $validated;
    }

    private static function extract_openai_text(array $body)
    {
        if (!empty($body['output_text']) && is_string($body['output_text'])) {
            return $body['output_text'];
        }
        if (empty($body['output']) || !is_array($body['output'])) {
            return '';
        }
        foreach ($body['output'] as $output) {
            if (empty($output['content']) || !is_array($output['content'])) {
                continue;
            }
            foreach ($output['content'] as $content) {
                if (!empty($content['text']) && is_string($content['text'])) {
                    return $content['text'];
                }
            }
        }
        return '';
    }

    private static function provider_key()
    {
        if (defined('JAZIREH_APOD_LOCALIZER_API_KEY') && JAZIREH_APOD_LOCALIZER_API_KEY) {
            return JAZIREH_APOD_LOCALIZER_API_KEY;
        }
        if (defined('OPENAI_API_KEY') && OPENAI_API_KEY) {
            return OPENAI_API_KEY;
        }
        $env_key = getenv('JAZIREH_APOD_LOCALIZER_API_KEY') ?: getenv('OPENAI_API_KEY');
        return $env_key ?: '';
    }

    private static function has_provider_key()
    {
        return self::provider_key() !== '';
    }

    private static function record_failure($date, $error)
    {
        $error = self::public_error_code($error);
        $post_id = Jazireh_APOD_Editorial::find_id_by_date($date);
        if ($post_id) {
            Jazireh_APOD_Editorial::mark_failed($post_id, $error);
        }
        self::record_monitor($date, 'failed', $error);
    }

    private static function record_monitor($date, $status, $error)
    {
        update_option(self::OPTION_MONITOR, array(
            'latestDate' => self::sanitize_date($date),
            'status' => sanitize_key($status),
            'lastAttemptAt' => current_time(DATE_ATOM),
            'lastSuccessAt' => $status === 'auto_ready' || $status === 'ready' ? current_time(DATE_ATOM) : (self::monitor()['lastSuccessAt'] ?? ''),
            'lastError' => sanitize_text_field((string) $error),
        ), false);
    }

    private static function public_error_code($error)
    {
        return is_wp_error($error) ? sanitize_key((string) $error->get_error_code()) : sanitize_key((string) $error ?: 'unknown_provider_failure');
    }

    private static function classify_wp_error(WP_Error $error)
    {
        $message = strtolower((string) $error->get_error_message());
        $data = $error->get_error_data();
        $code = sanitize_key((string) $error->get_error_code());
        $serialized = strtolower(wp_json_encode(array(
            'code' => $code,
            'message' => $message,
            'data' => $data,
        )));

        if (strpos($serialized, 'timed out') !== false || strpos($serialized, 'timeout') !== false) {
            return new WP_Error('provider_timeout', 'Localization provider request timed out.');
        }
        if (strpos($serialized, 'proxy') !== false || strpos($serialized, '127.0.0.1:10808') !== false) {
            return new WP_Error('proxy_failure', 'Localization provider proxy request failed.');
        }
        if (strpos($serialized, 'ssl') !== false || strpos($serialized, 'tls') !== false || strpos($serialized, 'certificate') !== false) {
            return new WP_Error('tls_failure', 'Localization provider TLS connection failed.');
        }
        if (strpos($serialized, 'resolve host') !== false || strpos($serialized, 'could not resolve') !== false || strpos($serialized, 'connection refused') !== false || strpos($serialized, 'failed to connect') !== false) {
            return new WP_Error('network_failure', 'Localization provider network request failed.');
        }

        return new WP_Error('unknown_provider_failure', 'Localization provider request failed.');
    }

    private static function classify_provider_response($status, $body, $raw_body)
    {
        $status = (int) $status;
        $body = is_array($body) ? $body : array();
        $error = isset($body['error']) && is_array($body['error']) ? $body['error'] : array();
        $provider_code = sanitize_key((string) ($error['code'] ?? ''));
        $provider_type = sanitize_key((string) ($error['type'] ?? ''));
        $provider_message = strtolower((string) ($error['message'] ?? ''));
        $context = strtolower($provider_code . ' ' . $provider_type . ' ' . $provider_message . ' ' . (string) $raw_body);

        if ($status === 401 || $provider_code === 'invalid_api_key' || strpos($context, 'incorrect api key') !== false || strpos($context, 'authentication') !== false) {
            return new WP_Error('authentication_failed', 'Localization provider authentication failed.');
        }
        if ($status === 429 && (strpos($context, 'insufficient_quota') !== false || strpos($context, 'billing') !== false || strpos($context, 'quota') !== false)) {
            return new WP_Error('insufficient_quota', 'Localization provider quota is exhausted.');
        }
        if ($status === 429) {
            return new WP_Error('rate_limited', 'Localization provider rate limit was reached.');
        }
        if ($status === 404 || $status === 400) {
            if (strpos($context, 'model') !== false && (strpos($context, 'not found') !== false || strpos($context, 'does not exist') !== false || strpos($context, 'unsupported') !== false)) {
                return new WP_Error('model_unavailable', 'Localization provider model is unavailable.');
            }
            return new WP_Error('invalid_provider_request', 'Localization provider rejected the request.');
        }
        if ($status >= 500 && $status <= 599) {
            return new WP_Error('unknown_provider_failure', 'Localization provider returned a server error.');
        }

        return new WP_Error('invalid_provider_response', 'Localization provider returned an unusable response.');
    }

    private static function contains_persian($value)
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}]/u', (string) $value);
    }

    private static function sanitize_date($date)
    {
        $date = sanitize_text_field((string) $date);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
    }
}
