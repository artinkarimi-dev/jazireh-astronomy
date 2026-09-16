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

function mark_manual_translation($post_id, $hash, $suffix = '')
{
    update_post_meta($post_id, Jazireh_APOD_Editorial::META_TITLE_FA, 'عنوان فارسی معتبر' . $suffix);
    update_post_meta($post_id, Jazireh_APOD_Editorial::META_SUMMARY_FA, 'خلاصه فارسی معتبر که از متن اصلی ناسا تهیه شده است' . $suffix);
    update_post_meta($post_id, Jazireh_APOD_Editorial::META_CONTENT_FA, 'ترجمه کامل فارسی معتبر برای نسخه فعلی منبع ناسا. عدد 42 و شناسه IC 348 بدون تغییر حفظ شده‌اند.' . $suffix);
    update_post_meta($post_id, Jazireh_APOD_Editorial::META_STATUS, Jazireh_APOD_Editorial::STATUS_MANUAL_READY);
    update_post_meta($post_id, Jazireh_APOD_Editorial::META_TRANSLATION_SOURCE_HASH, $hash);
    update_post_meta($post_id, Jazireh_APOD_Editorial::META_REVIEWED_AT, current_time(DATE_ATOM));
}

$dates = array('2099-01-01', '2099-01-02');
foreach ($dates as $date) {
    cleanup_apod_date($date);
}

try {
    update_option(Jazireh_APOD_Localizer::OPTION_MONITOR, array(), false);
    add_filter('jazireh_apod_localizer_enabled', '__return_false');

    $item_a = source_item('2099-01-01', 'Synthetic APOD IC 348', 'NASA explanation with number 42 and object IC 348.');
    $hash_a = Jazireh_APOD_Editorial::source_hash($item_a);
    Jazireh_APOD_Localizer::maybe_queue_latest(array($item_a));
    $post_id = Jazireh_APOD_Editorial::find_id_by_date('2099-01-01');
    assert_true($post_id > 0, 'TEST A: APOD fetch did not create pending editorial record.');
    assert_true(get_post_meta($post_id, Jazireh_APOD_Editorial::META_STATUS, true) === Jazireh_APOD_Editorial::STATUS_PENDING, 'TEST A: new APOD should be pending manual translation.');
    assert_true(get_post_meta($post_id, Jazireh_APOD_Editorial::META_SOURCE_HASH, true) === $hash_a, 'TEST A: source hash was not stored.');
    assert_true(!Jazireh_APOD_Editorial::has_usable_for_date('2099-01-01', $hash_a), 'TEST A: untranslated APOD must not expose Persian text.');

    $attempts_before = (int) get_post_meta($post_id, Jazireh_APOD_Editorial::META_ATTEMPTS, true);
    Jazireh_APOD_Localizer::process_date('2099-01-01');
    $attempts_after = (int) get_post_meta($post_id, Jazireh_APOD_Editorial::META_ATTEMPTS, true);
    assert_true($attempts_after === $attempts_before, 'TEST B: disabled provider should not attempt automatic translation.');

    mark_manual_translation($post_id, $hash_a);
    $ready_a = Jazireh_APOD_Editorial::get_ready_for_date('2099-01-01', $hash_a);
    assert_true(is_array($ready_a), 'TEST C: reviewed manual translation was not usable.');
    assert_true($ready_a['translationSourceHash'] === $hash_a, 'TEST C: manual translation was not bound to current source hash.');

    Jazireh_APOD_Localizer::maybe_queue_latest(array($item_a));
    $status_after_same = get_post_meta($post_id, Jazireh_APOD_Editorial::META_STATUS, true);
    assert_true($status_after_same === Jazireh_APOD_Editorial::STATUS_MANUAL_READY, 'TEST D: unchanged APOD should not invalidate reviewed translation.');

    $item_b = source_item('2099-01-01', 'Synthetic APOD IC 348', 'NASA edited explanation with number 84 and object IC 348.');
    $hash_b = Jazireh_APOD_Editorial::source_hash($item_b);
    assert_true($hash_b !== $hash_a, 'TEST E: edited source hash did not change.');
    Jazireh_APOD_Localizer::maybe_queue_latest(array($item_b));
    assert_true(!Jazireh_APOD_Editorial::has_usable_for_date('2099-01-01', $hash_b), 'TEST E: stale reviewed translation was exposed for edited source.');
    assert_true(get_post_meta($post_id, Jazireh_APOD_Editorial::META_STATUS, true) === Jazireh_APOD_Editorial::STATUS_PENDING, 'TEST E: edited APOD should return to pending manual translation.');

    mark_manual_translation($post_id, $hash_b, ' نسخه دوم');
    $ready_b = Jazireh_APOD_Editorial::get_ready_for_date('2099-01-01', $hash_b);
    assert_true(is_array($ready_b) && $ready_b['translationSourceHash'] === $hash_b, 'TEST F: updated manual translation was not bound to edited source.');

    $item_new_date = source_item('2099-01-02', 'Synthetic New Date APOD', 'NASA explanation for a different APOD date.');
    $hash_new_date = Jazireh_APOD_Editorial::source_hash($item_new_date);
    Jazireh_APOD_Localizer::maybe_queue_latest(array($item_new_date));
    $new_id = Jazireh_APOD_Editorial::find_id_by_date('2099-01-02');
    assert_true($new_id > 0, 'TEST G: new APOD date did not create an editorial record.');
    assert_true(get_post_meta($new_id, Jazireh_APOD_Editorial::META_SOURCE_HASH, true) === $hash_new_date, 'TEST G: new APOD date did not store source hash.');
    assert_true(get_post_meta($new_id, Jazireh_APOD_Editorial::META_STATUS, true) === Jazireh_APOD_Editorial::STATUS_PENDING, 'TEST G: new APOD date should be pending manual translation.');

    $site_response = wp_remote_get(home_url('/wp-json/jazireh/v1/apod?limit=1'), array('timeout' => 15));
    $body = wp_remote_retrieve_body($site_response);
    assert_true(strpos($body, 'OPENAI_API_KEY') === false && strpos($body, 'JAZIREH_APOD_LOCALIZER_API_KEY') === false, 'TEST H: REST exposed provider key markers.');

    echo "APOD manual editorial localization contract tests passed.\n";
} finally {
    remove_filter('jazireh_apod_localizer_enabled', '__return_false');
    foreach ($dates as $date) {
        cleanup_apod_date($date);
    }
}
