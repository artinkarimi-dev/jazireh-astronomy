<?php

$wordpress_root = 'C:/xampp/htdocs/wordpress';
$wp_load = $wordpress_root . '/wp-load.php';

if (!is_file($wp_load)) {
    fwrite(STDERR, "WordPress bootstrap was not found.\n");
    exit(1);
}

require $wp_load;

if (!class_exists('Jazireh_Daily')) {
    fwrite(STDERR, "Jazireh_Daily is not available.\n");
    exit(1);
}

$secret = Jazireh_Daily::sync_secret();
if (!is_string($secret) || $secret === '') {
    fwrite(STDERR, "Jazireh Daily sync secret is unavailable.\n");
    exit(1);
}

echo $secret;
