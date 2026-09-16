<?php

declare(strict_types=1);

require 'C:/xampp/htdocs/wordpress/wp-load.php';

if (!class_exists('Jazireh_APOD_Service') || !class_exists('Jazireh_APOD_Editorial')) {
    fwrite(STDERR, "APOD classes are unavailable.\n");
    exit(1);
}

function assert_apod_video_contract($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$api_key = defined('JAZIREH_NASA_API_KEY') && JAZIREH_NASA_API_KEY
    ? JAZIREH_NASA_API_KEY
    : (defined('NASA_API_KEY') && NASA_API_KEY ? NASA_API_KEY : 'DEMO_KEY');

$url = add_query_arg(array(
    'api_key' => $api_key,
    'date' => '2026-09-13',
    'thumbs' => 'true',
), 'https://api.nasa.gov/planetary/apod');

$response = wp_remote_get($url, array('timeout' => 20));
if (is_wp_error($response)) {
    fwrite(STDERR, "NASA video APOD request failed: " . $response->get_error_message() . "\n");
    exit(1);
}

$status = wp_remote_retrieve_response_code($response);
$data = json_decode(wp_remote_retrieve_body($response), true);
assert_apod_video_contract($status >= 200 && $status < 300 && is_array($data), 'NASA video APOD response was invalid.');
assert_apod_video_contract(($data['date'] ?? '') === '2026-09-13', 'NASA video APOD date changed unexpectedly.');
assert_apod_video_contract(($data['media_type'] ?? '') === 'video', 'NASA APOD fixture date is not a real video day.');
assert_apod_video_contract(!empty($data['title']), 'NASA video APOD title is missing.');
assert_apod_video_contract(!empty($data['explanation']), 'NASA video APOD explanation is missing.');
assert_apod_video_contract(!empty($data['url']) && strpos((string) $data['url'], 'https://') === 0, 'NASA video APOD URL is missing or not HTTPS.');

$formatter = new ReflectionMethod('Jazireh_APOD_Service', 'format_item');
$formatter->setAccessible(true);
$item = $formatter->invoke(null, $data);

assert_apod_video_contract($item['date'] === '2026-09-13', 'Formatted video date is incorrect.');
assert_apod_video_contract($item['mediaType'] === 'video', 'Formatted video media type is incorrect.');
assert_apod_video_contract($item['titleOriginal'] === html_entity_decode(wp_strip_all_tags($data['title']), ENT_QUOTES, 'UTF-8'), 'Original video title was corrupted.');
assert_apod_video_contract($item['contentOriginal'] !== '', 'Original video explanation was not preserved.');
assert_apod_video_contract($item['sourceUrl'] === esc_url_raw($data['url']), 'Video source URL was not preserved.');
assert_apod_video_contract($item['mediaUrl'] === esc_url_raw($data['url']), 'Video media URL was not preserved.');
assert_apod_video_contract($item['hdUrl'] === '', 'Video APOD should not expose image HD URL.');
assert_apod_video_contract((bool) preg_match('/^[a-f0-9]{64}$/', $item['sourceHash']), 'Video APOD source hash is invalid.');
assert_apod_video_contract($item['translationStatus'] === 'missing' || $item['translationStatus'] === 'pending', 'Unexpected video translation status.');

$edited = $item;
$edited['contentOriginal'] .= ' NASA source text changed.';
$edited['content'] = $edited['contentOriginal'];
$edited_hash = Jazireh_APOD_Editorial::source_hash($edited);
assert_apod_video_contract($edited_hash !== $item['sourceHash'], 'Changed video APOD source did not change hash.');

$site_response = wp_remote_get(home_url('/wp-json/jazireh/v1/apod?limit=1'), array('timeout' => 15));
$body = wp_remote_retrieve_body($site_response);
assert_apod_video_contract(strpos($body, 'JAZIREH_NASA_API_KEY') === false, 'REST exposed NASA key marker.');
assert_apod_video_contract(strpos($body, 'OPENAI_API_KEY') === false && strpos($body, 'JAZIREH_APOD_LOCALIZER_API_KEY') === false, 'REST exposed translation key markers.');

echo "APOD real video-day contract tests passed.\n";
