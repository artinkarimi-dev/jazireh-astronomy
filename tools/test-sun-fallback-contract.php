<?php

require_once 'C:/xampp/htdocs/wordpress/wp-load.php';

function assert_sun_contract($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

function sun_reflect($method)
{
    $reflection = new ReflectionClass('Jazireh_Sun_Service');
    $target = $reflection->getMethod($method);
    $target->setAccessible(true);
    return $target;
}

function sun_http_response($body, $content_type = 'application/json', $status = 200)
{
    return array(
        'headers' => array('content-type' => $content_type),
        'body' => $body,
        'response' => array('code' => $status, 'message' => 'OK'),
        'cookies' => array(),
        'filename' => null,
    );
}

function with_sun_http_mock($callback, $assertion)
{
    add_filter('pre_http_request', $callback, 10, 3);
    try {
        return $assertion();
    } finally {
        remove_filter('pre_http_request', $callback, 10);
    }
}

$fetch_latest_payload = sun_reflect('fetch_latest_payload');
$remote_image_is_available = sun_reflect('remote_image_is_available');
$safe_image_url = sun_reflect('safe_image_url');

$original_cache = Jazireh_Widgets::get_cached(Jazireh_Sun_Service::WIDGET_KEY);
$original_last_good = Jazireh_Widgets::last_good(Jazireh_Sun_Service::WIDGET_KEY);

try {
    assert_sun_contract($safe_image_url->invoke(null, 'javascript:alert(1)') === '', 'Unsafe image scheme was accepted.');
    assert_sun_contract($safe_image_url->invoke(null, 'http://example.com/sun.jpg') === '', 'Non-HTTPS image URL was accepted.');
    assert_sun_contract($safe_image_url->invoke(null, Jazireh_Sun_Service::SDO_LATEST_304) === Jazireh_Sun_Service::SDO_LATEST_304, 'Valid HTTPS SDO image URL was rejected.');

    with_sun_http_mock(
        function ($preempt, $args, $url) {
            if ($url === 'https://example.test/not-image') {
                return sun_http_response('not an image', 'text/html');
            }
            return $preempt;
        },
        function () use ($remote_image_is_available) {
            assert_sun_contract($remote_image_is_available->invoke(null, 'https://example.test/not-image') === false, 'Invalid image content type was accepted.');
        }
    );

    $primary = with_sun_http_mock(
        function ($preempt, $args, $url) {
            if (strpos($url, '/getClosestImage/') !== false) {
                return sun_http_response(json_encode(array(
                    'id' => 123,
                    'date' => '2026-09-17T10:00:00Z',
                )));
            }
            if (strpos($url, '/takeScreenshot/') !== false) {
                return sun_http_response('', 'image/jpeg');
            }
            return $preempt;
        },
        function () use ($fetch_latest_payload) {
            return $fetch_latest_payload->invoke(null);
        }
    );
    assert_sun_contract(is_array($primary), 'Primary Helioviewer source did not return a payload.');
    assert_sun_contract(empty($primary['isFallback']), 'Primary Helioviewer payload was marked fallback.');
    assert_sun_contract($primary['provider'] === 'Helioviewer', 'Primary provider was not preserved.');
    assert_sun_contract($primary['observedAt'] === '2026-09-17T10:00:00+00:00', 'Primary observation timestamp was not preserved.');
    assert_sun_contract($primary['wavelength'] === '304 Å', 'Primary wavelength metadata changed.');

    $secondary = with_sun_http_mock(
        function ($preempt, $args, $url) {
            if (strpos($url, '/getClosestImage/') !== false) {
                return new WP_Error('simulated_timeout', 'Simulated timeout.');
            }
            if ($url === Jazireh_Sun_Service::SDO_LATEST_304) {
                return sun_http_response('', 'image/jpeg');
            }
            return $preempt;
        },
        function () use ($fetch_latest_payload) {
            return $fetch_latest_payload->invoke(null);
        }
    );
    assert_sun_contract(is_array($secondary), 'Secondary SDO fallback did not return a payload.');
    assert_sun_contract(!empty($secondary['isFallback']), 'Secondary SDO payload was not marked fallback.');
    assert_sun_contract($secondary['provider'] === 'NASA SDO', 'Secondary provider was not preserved.');
    assert_sun_contract($secondary['observedAt'] === '', 'Secondary SDO fallback must not invent observation time.');

    $official_fallback = with_sun_http_mock(
        function ($preempt, $args, $url) {
            if (strpos($url, '/getClosestImage/') !== false) {
                return sun_http_response(json_encode(array('id' => 123, 'date' => '2026-09-17T10:00:00Z')));
            }
            return sun_http_response('not image', 'text/html');
        },
        function () use ($fetch_latest_payload) {
            return $fetch_latest_payload->invoke(null);
        }
    );
    assert_sun_contract(is_array($official_fallback), 'Official SDO fallback was not served when server validation failed.');
    assert_sun_contract(!empty($official_fallback['isFallback']), 'Official SDO fallback was not marked fallback.');
    assert_sun_contract($official_fallback['provider'] === 'NASA SDO', 'Official SDO fallback provider was not preserved.');
    assert_sun_contract(($official_fallback['observedAt'] ?? '') === '', 'Official SDO fallback must not invent observation time.');
    assert_sun_contract(strpos($official_fallback['fallbackReason'], 'could not be validated') !== false, 'Official SDO fallback did not disclose validation failure.');

    Jazireh_Widgets::delete_cached(Jazireh_Sun_Service::WIDGET_KEY);
    update_option('jazireh_widget_last_good_sun', Jazireh_Widgets::ready(Jazireh_Sun_Service::WIDGET_KEY, $primary, array(
        'source' => $primary['source'],
        'sourceUrl' => $primary['sourceUrl'],
    )), false);

    $stale = with_sun_http_mock(
        function () {
            return new WP_Error('simulated_total_failure', 'Simulated total failure.');
        },
        function () {
            return Jazireh_Sun_Service::widget(array('forceRefresh' => true));
        }
    );
    assert_sun_contract(is_array($stale) && $stale['status'] === Jazireh_Widgets::STATE_STALE, 'Official fallback was not served as stale before last-known-good.');
    assert_sun_contract(($stale['data']['image'] ?? '') === Jazireh_Sun_Service::SDO_LATEST_304, 'Official fallback did not take precedence over last-known-good.');
    assert_sun_contract(($stale['data']['observedAt'] ?? '') === '', 'Official fallback must not invent observation time.');

    Jazireh_Widgets::delete_cached(Jazireh_Sun_Service::WIDGET_KEY);
    delete_option('jazireh_widget_last_good_sun');
    $failed = with_sun_http_mock(
        function () {
            return new WP_Error('simulated_total_failure', 'Simulated total failure.');
        },
        function () {
            return Jazireh_Sun_Service::widget(array('forceRefresh' => true));
        }
    );
    assert_sun_contract(is_array($failed) && $failed['status'] === Jazireh_Widgets::STATE_STALE, 'No-cache official fallback did not return a stale state.');
    assert_sun_contract(($failed['data']['image'] ?? '') === Jazireh_Sun_Service::SDO_LATEST_304, 'No-cache official fallback did not expose the SDO image.');

    Jazireh_Sun_Service::maybe_schedule_refresh();
    assert_sun_contract(wp_next_scheduled(Jazireh_Sun_Service::REFRESH_HOOK) !== false, 'Sun refresh cron hook is not scheduled.');
    assert_sun_contract(wp_get_schedule(Jazireh_Sun_Service::REFRESH_HOOK) === 'jazireh_every_30_minutes', 'Sun refresh recurrence is incorrect.');

    $response = wp_remote_get(home_url('/wp-json/jazireh/v1/widgets/sun'), array('timeout' => 15));
    assert_sun_contract(!is_wp_error($response), 'Runtime Sun widget REST failed.');
    $body = wp_remote_retrieve_body($response);
    assert_sun_contract(wp_remote_retrieve_response_code($response) === 200, 'Runtime Sun widget REST did not return 200.');
    assert_sun_contract(strpos($body, 'JAZIREH_') === false && strpos($body, 'api_key') === false, 'Runtime Sun REST exposed credential markers.');

    echo "Sun fallback contract tests passed.\n";
} finally {
    Jazireh_Widgets::delete_cached(Jazireh_Sun_Service::WIDGET_KEY);
    if (is_array($original_cache)) {
        Jazireh_Widgets::set_cached(Jazireh_Sun_Service::WIDGET_KEY, $original_cache, Jazireh_Sun_Service::CACHE_TTL);
    }
    if (is_array($original_last_good)) {
        update_option('jazireh_widget_last_good_sun', $original_last_good, false);
    } else {
        delete_option('jazireh_widget_last_good_sun');
    }
}
