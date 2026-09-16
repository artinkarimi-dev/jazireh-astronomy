<?php

declare(strict_types=1);

require 'C:/xampp/htdocs/wordpress/wp-load.php';

if (!class_exists('Jazireh_APOD_Service')) {
    fwrite(STDERR, "APOD service class is unavailable.\n");
    exit(1);
}

function assert_apod_attribution($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function nasa_apod_attribution_item($date)
{
    $api_key = defined('JAZIREH_NASA_API_KEY') && JAZIREH_NASA_API_KEY
        ? JAZIREH_NASA_API_KEY
        : (defined('NASA_API_KEY') && NASA_API_KEY ? NASA_API_KEY : 'DEMO_KEY');

    $response = wp_remote_get(add_query_arg(array(
        'api_key' => $api_key,
        'date' => $date,
        'thumbs' => 'true',
    ), 'https://api.nasa.gov/planetary/apod'), array('timeout' => 20));

    if (is_wp_error($response)) {
        fwrite(STDERR, "NASA APOD request failed for {$date}: " . $response->get_error_message() . "\n");
        exit(1);
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    assert_apod_attribution(is_array($data), "NASA APOD response was invalid for {$date}.");

    $formatter = new ReflectionMethod('Jazireh_APOD_Service', 'format_item');
    $formatter->setAccessible(true);
    return array($data, $formatter->invoke(null, $data));
}

list($current_raw, $current) = nasa_apod_attribution_item('2026-09-16');
assert_apod_attribution(($current_raw['copyright'] ?? '') === '', 'Current APOD unexpectedly has copyright in test fixture.');
assert_apod_attribution($current['sourceName'] === 'NASA Astronomy Picture of the Day', 'Current APOD source name missing.');
assert_apod_attribution($current['sourceUrl'] === 'https://apod.nasa.gov/apod/ap260916.html', 'Current APOD source URL incorrect.');
assert_apod_attribution($current['mediaUrl'] === esc_url_raw($current_raw['url']), 'Current APOD media URL incorrect.');
assert_apod_attribution($current['copyright'] === '', 'Current APOD fabricated copyright.');
assert_apod_attribution($current['credit'] === '', 'Current APOD fabricated credit.');
assert_apod_attribution($current['photographer'] === '', 'Current APOD fabricated photographer.');

list($image_raw, $image) = nasa_apod_attribution_item('2026-09-15');
assert_apod_attribution(($image_raw['copyright'] ?? '') === 'Arnaud Mariat', 'Copyright-present APOD fixture changed.');
assert_apod_attribution($image['mediaType'] === 'image', 'Copyright-present APOD should be an image.');
assert_apod_attribution($image['copyright'] === 'Arnaud Mariat', 'Image APOD copyright was not preserved.');
assert_apod_attribution($image['credit'] === 'Arnaud Mariat', 'Image APOD credit was not preserved.');
assert_apod_attribution($image['photographer'] === 'Arnaud Mariat', 'Image APOD photographer compatibility field was not preserved.');
assert_apod_attribution(strpos($image['copyright'], 'NASA') === false, 'Image APOD invented NASA ownership.');

list($video_raw, $video) = nasa_apod_attribution_item('2026-09-13');
assert_apod_attribution(($video_raw['copyright'] ?? '') === 'Paolo Girotti', 'Video APOD fixture changed.');
assert_apod_attribution($video['mediaType'] === 'video', 'Video APOD media type was not preserved.');
assert_apod_attribution($video['sourceUrl'] === 'https://apod.nasa.gov/apod/ap260913.html', 'Video APOD source URL incorrect.');
assert_apod_attribution($video['mediaUrl'] === esc_url_raw($video_raw['url']), 'Video APOD media URL incorrect.');
assert_apod_attribution($video['copyright'] === 'Paolo Girotti', 'Video APOD copyright was not preserved.');

$unsafe = array(
    'date' => '2099-03-01',
    'title' => 'Unsafe Source',
    'explanation' => 'Synthetic unsafe source URL normalization check.',
    'media_type' => 'image',
    'url' => 'javascript:alert(1)',
    'service_version' => 'v1',
);
$formatter = new ReflectionMethod('Jazireh_APOD_Service', 'format_item');
$formatter->setAccessible(true);
$unsafe_item = $formatter->invoke(null, $unsafe);
assert_apod_attribution($unsafe_item['mediaUrl'] === '', 'Unsafe media URL was exposed.');
assert_apod_attribution(strpos($unsafe_item['sourceUrl'], 'https://apod.nasa.gov/apod/') === 0, 'Unsafe source did not fall back to safe APOD page URL.');

$site_response = wp_remote_get(home_url('/wp-json/jazireh/v1/apod?limit=1'), array('timeout' => 15));
$body = wp_remote_retrieve_body($site_response);
assert_apod_attribution(strpos($body, 'JAZIREH_NASA_API_KEY') === false, 'REST exposed NASA key marker.');
assert_apod_attribution(strpos($body, 'OPENAI_API_KEY') === false && strpos($body, 'JAZIREH_APOD_LOCALIZER_API_KEY') === false, 'REST exposed translation key markers.');

echo "APOD attribution and copyright contract tests passed.\n";
