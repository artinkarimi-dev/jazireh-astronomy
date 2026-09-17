<?php

require_once 'C:/xampp/htdocs/wordpress/wp-load.php';

if (!class_exists('Jazireh_Moon_Service')) {
    fwrite(STDERR, "Jazireh_Moon_Service is not loaded.\n");
    exit(1);
}

$reflection = new ReflectionClass('Jazireh_Moon_Service');
$calculate = $reflection->getMethod('calculate_payload');
$calculate->setAccessible(true);

$failures = array();
$max_illumination_delta = 0.0;

function assert_true($condition, $message)
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }
}

function calculate_moon_payload($method, $iso)
{
    return $method->invoke(null, strtotime($iso));
}

function assert_phase($method, $iso, $phase, $illumination, $trend)
{
    global $max_illumination_delta;

    $payload = calculate_moon_payload($method, $iso);
    assert_true(is_array($payload), "{$iso} returns a payload");
    if (!is_array($payload)) {
        return null;
    }

    $delta = abs((float) $payload['illuminationPercent'] - $illumination);
    $max_illumination_delta = max($max_illumination_delta, $delta);

    assert_true($payload['phaseName'] === $phase, "{$iso} phase is {$phase}");
    assert_true($delta <= 0.2, "{$iso} illumination is within 0.2%, got {$payload['illuminationPercent']}");
    assert_true($payload['waxingWaning'] === $trend, "{$iso} trend is {$trend}");
    assert_true($payload['timeBasis'] === 'UTC', "{$iso} uses UTC");
    assert_true($payload['scope'] === 'global-geocentric', "{$iso} is global/geocentric");
    assert_true($payload['illuminationPercent'] >= 0 && $payload['illuminationPercent'] <= 100, "{$iso} illumination is clamped");
    assert_true($payload['moonAgeDays'] >= 0 && $payload['moonAgeDays'] <= $payload['lunationLengthDays'], "{$iso} age stays inside lunation");

    return $payload;
}

$new = assert_phase($calculate, '2026-09-11T03:27:00Z', 'new_moon', 0.0, 'waxing');
assert_true($new && $new['moonAgeDays'] <= 0.01, 'New Moon age resets to zero');

$first = assert_phase($calculate, '2026-09-18T20:44:00Z', 'first_quarter', 50.0, 'waxing');
assert_true($first && $first['moonAgeDays'] > 7 && $first['moonAgeDays'] < 8, 'First Quarter age is plausible');

$full = assert_phase($calculate, '2026-09-26T16:49:00Z', 'full_moon', 100.0, 'waning');
assert_true($full && $full['moonAgeDays'] > 15 && $full['moonAgeDays'] < 16, 'Full Moon age is plausible');

$after_full = calculate_moon_payload($calculate, '2026-09-26T16:50:00Z');
assert_true(is_array($after_full) && $after_full['waxingWaning'] === 'waning', 'Full Moon transition becomes waning afterward');

$last = assert_phase($calculate, '2026-10-03T13:25:00Z', 'last_quarter', 50.0, 'waning');
assert_true($last && $last['moonAgeDays'] > 22 && $last['moonAgeDays'] < 23, 'Last Quarter age is plausible');

$before_new = calculate_moon_payload($calculate, '2026-10-10T15:49:00Z');
$after_new = calculate_moon_payload($calculate, '2026-10-10T15:51:00Z');
assert_true(is_array($before_new) && $before_new['waxingWaning'] === 'waning', 'Minute before New Moon remains waning');
assert_true(is_array($after_new) && $after_new['waxingWaning'] === 'waxing', 'Minute after New Moon becomes waxing');
assert_true(is_array($after_new) && $after_new['moonAgeDays'] <= 0.01, 'Age wraps after New Moon');

$utc_boundary = calculate_moon_payload($calculate, '2026-09-11T00:30:00Z');
assert_true(is_array($utc_boundary) && $utc_boundary['waxingWaning'] === 'waning', 'UTC boundary does not shift to local Tehran date');

$leap_fallback = calculate_moon_payload($calculate, '2028-02-29T12:00:00Z');
assert_true(is_array($leap_fallback), 'Leap-day fallback returns a payload');
assert_true($leap_fallback['illuminationPercent'] >= 0 && $leap_fallback['illuminationPercent'] <= 100, 'Leap-day fallback illumination is clamped');
assert_true($leap_fallback['moonAgeDays'] >= 0 && $leap_fallback['moonAgeDays'] <= $leap_fallback['lunationLengthDays'], 'Leap-day fallback age is valid');

$invalid = $calculate->invoke(null, 0);
assert_true(is_wp_error($invalid), 'Invalid timestamp returns WP_Error');

Jazireh_Widgets::delete_cached('moon');
$runtime = Jazireh_Moon_Service::refresh();
assert_true(is_array($runtime), 'Runtime Moon refresh returns an array');
assert_true(($runtime['status'] ?? '') === 'ready', 'Runtime Moon widget is ready');
assert_true(($runtime['data']['scope'] ?? '') === 'global-geocentric', 'Runtime Moon widget exposes global/geocentric scope');
assert_true(($runtime['data']['timeBasis'] ?? '') === 'UTC', 'Runtime Moon widget exposes UTC basis');

$rest = wp_remote_get(home_url('/wp-json/jazireh/v1/widgets/moon'), array('timeout' => 15));
assert_true(!is_wp_error($rest), 'Moon REST endpoint responds');
if (!is_wp_error($rest)) {
    $body = wp_remote_retrieve_body($rest);
    $code = wp_remote_retrieve_response_code($rest);
    $decoded = json_decode($body, true);
    $widget = isset($decoded['success']) && isset($decoded['data']) ? $decoded['data'] : $decoded;
    assert_true($code === 200, "Moon REST endpoint returns 200, got {$code}");
    assert_true(is_array($decoded), 'Moon REST endpoint returns JSON');
    assert_true(($widget['status'] ?? '') === 'ready', 'Moon REST endpoint status is ready');
    assert_true(strpos($body, 'JAZIREH_') === false && stripos($body, 'api_key') === false, 'Moon REST endpoint exposes no secrets');
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    exit(1);
}

echo "Moon phase contract passed. Max illumination delta: " . round($max_illumination_delta, 3) . "%\n";
