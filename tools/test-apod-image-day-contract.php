<?php

declare(strict_types=1);

require 'C:/xampp/htdocs/wordpress/wp-load.php';

if (!class_exists('Jazireh_APOD_Service')) {
    fwrite(STDERR, "APOD service class is unavailable.\n");
    exit(1);
}

function assert_apod_image_contract($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$formatter = new ReflectionMethod('Jazireh_APOD_Service', 'format_item');
$formatter->setAccessible(true);

$standard_url = 'https://apod.nasa.gov/apod/image/test/example_1024.jpg';
$hd_url = 'https://apod.nasa.gov/apod/image/test/example.jpg';
$image_item = $formatter->invoke(null, array(
    'date' => '2099-02-01',
    'title' => 'Synthetic Image APOD',
    'explanation' => 'Synthetic image explanation for APOD image-day contract coverage.',
    'media_type' => 'image',
    'url' => $standard_url,
    'hdurl' => $hd_url,
    'copyright' => 'NASA',
    'service_version' => 'v1',
));

assert_apod_image_contract($image_item['mediaType'] === 'image', 'Image-day media type was not preserved.');
assert_apod_image_contract($image_item['image'] === $standard_url, 'Image-day primary image should use NASA url, not hdurl.');
assert_apod_image_contract($image_item['sourceUrl'] === $standard_url, 'Image-day sourceUrl should preserve NASA url.');
assert_apod_image_contract($image_item['mediaUrl'] === $standard_url, 'Image-day mediaUrl should preserve NASA url.');
assert_apod_image_contract($image_item['hdUrl'] === $hd_url, 'Image-day hdUrl should remain available separately.');
assert_apod_image_contract((bool) preg_match('/^[a-f0-9]{64}$/', $image_item['sourceHash']), 'Image-day source hash is invalid.');

$no_hd_item = $formatter->invoke(null, array(
    'date' => '2099-02-02',
    'title' => 'Synthetic No HD APOD',
    'explanation' => 'Synthetic explanation without an HD image URL.',
    'media_type' => 'image',
    'url' => $standard_url,
    'service_version' => 'v1',
));

assert_apod_image_contract($no_hd_item['image'] === $standard_url, 'Missing hdurl should not break the primary image URL.');
assert_apod_image_contract($no_hd_item['hdUrl'] === '', 'Missing hdurl should normalize to an empty string.');

$video_item = $formatter->invoke(null, array(
    'date' => '2099-02-03',
    'title' => 'Synthetic Video APOD',
    'explanation' => 'Synthetic video explanation.',
    'media_type' => 'video',
    'url' => 'https://www.youtube.com/embed/example',
    'thumbnail_url' => 'https://img.youtube.com/vi/example/hqdefault.jpg',
    'service_version' => 'v1',
));

assert_apod_image_contract($video_item['mediaType'] === 'video', 'Video media type was not preserved.');
assert_apod_image_contract($video_item['image'] === 'https://img.youtube.com/vi/example/hqdefault.jpg', 'Video APOD should use thumbnail_url as image.');
assert_apod_image_contract($video_item['sourceUrl'] === 'https://www.youtube.com/embed/example', 'Video source URL was not preserved.');

echo "APOD image-day rendering contract tests passed.\n";
