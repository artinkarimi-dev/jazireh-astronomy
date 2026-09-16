<?php

require_once 'C:/xampp/htdocs/wordpress/wp-load.php';

function assert_youtube_contract($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

function youtube_reflect($method)
{
    $reflection = new ReflectionClass('Jazireh_YouTube');
    $target = $reflection->getMethod($method);
    $target->setAccessible(true);
    return $target;
}

$channel_id = 'UCTcdXS_pSh5K74g0UmlzLuA';
$channel_meta = array(
    'channelId' => $channel_id,
    'uploadsPlaylistId' => 'UUTcdXS_pSh5K74g0UmlzLuA',
    'channelTitle' => 'Jazireh',
);

$sanitize_video_id = youtube_reflect('sanitize_video_id');
$sanitize_channel_id = youtube_reflect('sanitize_channel_id');
$normalize_payload = youtube_reflect('normalize_video_payload');
$feed_videos = youtube_reflect('feed_videos');
$fallback_or_error = youtube_reflect('fallback_or_error');

assert_youtube_contract($sanitize_channel_id->invoke(null, $channel_id) === $channel_id, 'Official Jazireh channel ID was rejected.');
assert_youtube_contract($sanitize_channel_id->invoke(null, 'bad-channel') === '', 'Malformed channel ID was accepted.');
assert_youtube_contract($sanitize_video_id->invoke(null, 'abcDEF_1234') === 'abcDEF_1234', 'Valid YouTube video ID was rejected.');
assert_youtube_contract($sanitize_video_id->invoke(null, 'https://example.com/embed/abcDEF_1234') === '', 'Malformed video ID was accepted.');

$safe = $normalize_payload->invoke(null, array(
    'id' => 'abcDEF_1234',
    'title' => 'Safe video',
    'description' => '<b>Clean me</b>',
    'thumbnail' => 'https://i.ytimg.com/vi/abcDEF_1234/hqdefault.jpg',
    'publishedAt' => '2026-09-16T00:00:00+00:00',
    'channelTitle' => 'Jazireh',
    'channelId' => $channel_id,
    'ingestSource' => 'youtube-official-feed',
));

assert_youtube_contract($safe['id'] === 'abcDEF_1234', 'Normalized video ID changed.');
assert_youtube_contract($safe['videoId'] === 'abcDEF_1234', 'Compatibility videoId is missing.');
assert_youtube_contract($safe['youtubeUrl'] === 'https://www.youtube.com/watch?v=abcDEF_1234', 'Canonical watch URL is incorrect.');
assert_youtube_contract($safe['sourceUrl'] === $safe['youtubeUrl'], 'Source URL should match canonical watch URL.');
assert_youtube_contract($safe['embedUrl'] === 'https://www.youtube.com/embed/abcDEF_1234', 'Trusted embed URL is incorrect.');
assert_youtube_contract($safe['poster'] === $safe['thumbnail'], 'Poster and thumbnail should match.');
assert_youtube_contract($safe['channelId'] === $channel_id, 'Channel identity was not preserved.');
assert_youtube_contract($safe['channelUrl'] === 'https://www.youtube.com/channel/' . $channel_id, 'Channel URL is incorrect.');
assert_youtube_contract($safe['description'] === 'Clean me', 'Description was not sanitized.');

$fallback_thumbnail = $normalize_payload->invoke(null, array(
    'id' => 'abcDEF_1234',
    'title' => 'Fallback thumbnail',
    'thumbnail' => 'https://example.com/not-youtube.jpg',
    'channelId' => $channel_id,
));
assert_youtube_contract($fallback_thumbnail['thumbnail'] === 'https://i.ytimg.com/vi/abcDEF_1234/hqdefault.jpg', 'Unsafe thumbnail host was not replaced.');

$real_videos = $feed_videos->invoke(null, $channel_meta, 6);
assert_youtube_contract(!is_wp_error($real_videos), 'Official YouTube feed failed: ' . (is_wp_error($real_videos) ? $real_videos->get_error_message() : 'unknown'));
assert_youtube_contract(is_array($real_videos), 'Official YouTube feed did not return an array.');
assert_youtube_contract(count($real_videos) > 0, 'Official Jazireh feed returned no public videos.');

$seen = array();
foreach ($real_videos as $video) {
    assert_youtube_contract(!empty($video['id']) && preg_match('/^[A-Za-z0-9_-]{6,}$/', $video['id']), 'Real video has invalid ID.');
    assert_youtube_contract(empty($seen[$video['id']]), 'Duplicate video ID returned from feed.');
    $seen[$video['id']] = true;
    assert_youtube_contract(!empty($video['title']), 'Real video title is missing.');
    assert_youtube_contract(strpos($video['youtubeUrl'], 'https://www.youtube.com/watch?v=') === 0, 'Real video canonical URL is invalid.');
    assert_youtube_contract(strpos($video['embedUrl'], 'https://www.youtube.com/embed/') === 0, 'Real video embed URL is invalid.');
    assert_youtube_contract($video['channelId'] === $channel_id, 'Real video channel ID does not match official channel.');
    assert_youtube_contract(!empty($video['thumbnail']) && strpos($video['thumbnail'], 'https://') === 0, 'Real video thumbnail is missing or unsafe.');
    assert_youtube_contract(!empty($video['fetchedAt']), 'Real video fetched timestamp is missing.');
}

set_transient(Jazireh_YouTube::LAST_GOOD_PREFIX . '3', array_slice($real_videos, 0, 3), HOUR_IN_SECONDS);
$fallback = $fallback_or_error->invoke(null, 3, new WP_Error('simulated_upstream_failure', 'Simulated upstream failure.'), $channel_meta);
assert_youtube_contract(is_array($fallback) && count($fallback) > 0, 'Last-known-good cache was not preserved after simulated upstream failure.');
assert_youtube_contract($fallback[0]['id'] === $real_videos[0]['id'], 'Fallback data does not match last-known-good cache.');

Jazireh_YouTube::maybe_schedule_refresh();
$next = wp_next_scheduled(Jazireh_YouTube::REFRESH_HOOK);
assert_youtube_contract($next !== false, 'YouTube refresh cron hook is not scheduled.');
assert_youtube_contract(wp_get_schedule(Jazireh_YouTube::REFRESH_HOOK) === 'jazireh_every_4_hours', 'YouTube refresh recurrence is not every 4 hours.');

$response = wp_remote_get(home_url('/wp-json/jazireh/v1/videos?limit=6'), array('timeout' => 15));
assert_youtube_contract(!is_wp_error($response), 'Runtime videos REST request failed.');
$body = wp_remote_retrieve_body($response);
assert_youtube_contract(wp_remote_retrieve_response_code($response) === 200, 'Runtime videos REST did not return 200.');
assert_youtube_contract(strpos($body, 'JAZIREH_YOUTUBE_API_KEY') === false && strpos($body, 'AIza') === false && strpos($body, 'googleapis') === false, 'Runtime videos REST exposed credential markers.');

echo "YouTube ingestion contract tests passed.\n";
