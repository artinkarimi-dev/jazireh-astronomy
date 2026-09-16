<?php

declare(strict_types=1);

require 'C:/xampp/htdocs/wordpress/wp-load.php';

if (!class_exists('Jazireh_APOD_Editorial') || !class_exists('Jazireh_APOD_Localizer')) {
    fwrite(STDERR, "APOD localization classes are unavailable.\n");
    exit(1);
}

function assert_true($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function cleanup_apod_date($date)
{
    $posts = get_posts(array(
        'post_type' => Jazireh_APOD_Editorial::POST_TYPE,
        'post_status' => array('publish', 'draft', 'pending', 'private'),
        'posts_per_page' => -1,
        'meta_query' => array(array('key' => Jazireh_APOD_Editorial::META_DATE, 'value' => $date)),
    ));
    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }
    delete_transient('jazireh_apod_source_' . $date);
}

function source_item($date, $title, $content)
{
    return array(
        'id' => abs(crc32($date)),
        'date' => $date,
        'title' => $title,
        'titleOriginal' => $title,
        'content' => $content,
        'contentOriginal' => $content,
        'excerpt' => wp_trim_words($content, 24, '...'),
        'excerptOriginal' => wp_trim_words($content, 24, '...'),
        'image' => 'https://apod.nasa.gov/apod/image/test/example.jpg',
        'mediaType' => 'image',
        'photographer' => 'NASA',
        'sourceUrl' => 'https://apod.nasa.gov/apod/image/test/example.jpg',
        'hdUrl' => 'https://apod.nasa.gov/apod/image/test/example-hd.jpg',
        'serviceVersion' => 'v1',
    );
}

$dates = array('2099-01-01', '2099-01-02');
foreach ($dates as $date) {
    cleanup_apod_date($date);
}

$provider_mode = 'success';
add_filter('jazireh_apod_localizer_provider_response', function ($response, $payload) use (&$provider_mode) {
    if ($provider_mode === 'fail') {
        return new WP_Error('provider_timeout', 'Synthetic provider failure.');
    }
    return array(
        'titleFa' => 'عنوان فارسی ' . $payload['date'],
        'summaryFa' => 'خلاصه فارسی معتبر برای ' . $payload['titleOriginal'],
        'contentFa' => 'این یک ترجمه فارسی معتبر برای منبع فعلی ناسا است و فقط برای آزمون چرخه ترجمه استفاده می‌شود. عدد 42 و شناسه IC 348 بدون تغییر حفظ شده‌اند.',
        'translationNotes' => array(),
        'sourceHash' => $payload['sourceHash'],
    );
}, 10, 2);

try {
    $item_a = source_item('2099-01-01', 'Synthetic APOD IC 348', 'NASA explanation with number 42 and object IC 348.');
    $hash_a = Jazireh_APOD_Editorial::source_hash($item_a);
    $post_id = Jazireh_APOD_Editorial::ensure_pending($item_a);
    assert_true($post_id > 0, 'TEST A: pending entry was not created.');
    assert_true(!Jazireh_APOD_Editorial::has_usable_for_date('2099-01-01', $hash_a), 'TEST A: new APOD should require translation.');

    assert_true(Jazireh_APOD_Localizer::process_date('2099-01-01'), 'TEST E: successful translation did not process.');
    $ready = Jazireh_APOD_Editorial::get_ready_for_date('2099-01-01', $hash_a);
    assert_true(is_array($ready), 'TEST E: translated APOD is not ready.');
    assert_true($ready['translationSourceHash'] === $hash_a, 'TEST E: translation source hash was not stored.');
    $attempts_after_first = (int) get_post_meta($post_id, Jazireh_APOD_Editorial::META_ATTEMPTS, true);

    assert_true(Jazireh_APOD_Localizer::process_date('2099-01-01'), 'TEST B: idempotent second processing failed.');
    $attempts_after_second = (int) get_post_meta($post_id, Jazireh_APOD_Editorial::META_ATTEMPTS, true);
    assert_true($attempts_after_second === $attempts_after_first, 'TEST B: duplicate translation attempt occurred for same source hash.');

    update_post_meta($post_id, Jazireh_APOD_Editorial::META_STATUS, Jazireh_APOD_Editorial::STATUS_MANUAL_READY);
    update_post_meta($post_id, Jazireh_APOD_Editorial::META_TRANSLATION_SOURCE_HASH, $hash_a);
    $item_b = source_item('2099-01-01', 'Synthetic APOD IC 348', 'NASA edited explanation with number 84 and object IC 348.');
    $hash_b = Jazireh_APOD_Editorial::source_hash($item_b);
    Jazireh_APOD_Editorial::remember_source_item($item_b);
    Jazireh_APOD_Editorial::ensure_pending($item_b);
    assert_true($hash_b !== $hash_a, 'TEST C: edited source hash did not change.');
    assert_true(!Jazireh_APOD_Editorial::has_usable_for_date('2099-01-01', $hash_b), 'TEST F: reviewed old translation should not be usable for edited source.');
    assert_true(get_post_meta($post_id, Jazireh_APOD_Editorial::META_STATUS, true) === Jazireh_APOD_Editorial::STATUS_PENDING, 'TEST C/F: old reviewed translation was not moved back into pending lifecycle.');

    $provider_mode = 'fail';
    Jazireh_APOD_Localizer::process_date('2099-01-01');
    assert_true(!Jazireh_APOD_Editorial::has_usable_for_date('2099-01-01', $hash_b), 'TEST D: failed translation should not expose old mismatched Persian text.');
    assert_true(get_post_meta($post_id, Jazireh_APOD_Editorial::META_STATUS, true) === Jazireh_APOD_Editorial::STATUS_FAILED, 'TEST D: failed provider did not mark translation failed.');

    $provider_mode = 'success';
    update_post_meta($post_id, Jazireh_APOD_Editorial::META_STATUS, Jazireh_APOD_Editorial::STATUS_PENDING);
    assert_true(Jazireh_APOD_Localizer::process_date('2099-01-01'), 'TEST C: retranslation after source edit failed.');
    $ready_b = Jazireh_APOD_Editorial::get_ready_for_date('2099-01-01', $hash_b);
    assert_true(is_array($ready_b) && $ready_b['translationSourceHash'] === $hash_b, 'TEST C: edited source translation was not rebound to new hash.');

    $item_new_date = source_item('2099-01-02', 'Synthetic New Date APOD', 'NASA explanation for a different APOD date.');
    $hash_new_date = Jazireh_APOD_Editorial::source_hash($item_new_date);
    Jazireh_APOD_Editorial::ensure_pending($item_new_date);
    assert_true(Jazireh_APOD_Localizer::process_date('2099-01-02'), 'TEST H: new APOD date did not process.');
    assert_true(is_array(Jazireh_APOD_Editorial::get_ready_for_date('2099-01-02', $hash_new_date)), 'TEST H: new APOD date did not produce a current translation.');

    $site_response = wp_remote_get(home_url('/wp-json/jazireh/v1/apod?limit=1'), array('timeout' => 15));
    $body = wp_remote_retrieve_body($site_response);
    assert_true(strpos($body, 'OPENAI_API_KEY') === false && strpos($body, 'JAZIREH_APOD_LOCALIZER_API_KEY') === false, 'TEST G: REST exposed provider key markers.');

    echo "APOD localization contract tests passed.\n";
} finally {
    foreach ($dates as $date) {
        cleanup_apod_date($date);
    }
}
