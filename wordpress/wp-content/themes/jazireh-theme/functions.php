<?php

if (!defined('ABSPATH')) {
    exit;
}

function jazireh_theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('script', 'style'));
    add_theme_support('custom-logo', array(
        'height' => 120,
        'width' => 120,
        'flex-height' => true,
        'flex-width' => true,
    ));
    add_theme_support('site-icon');

    register_nav_menus(array(
        'jazireh_primary_menu' => 'Jazireh Primary Menu',
        'jazireh_mobile_menu' => 'Jazireh Mobile Menu',
        'jazireh_footer_menu' => 'Jazireh Footer Menu',
    ));
}
add_action('after_setup_theme', 'jazireh_theme_setup');

function jazireh_theme_request_path()
{
    $request_path = wp_parse_url(isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '/', PHP_URL_PATH);
    $home_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
    if ($home_path && strpos($request_path, $home_path) === 0) {
        $request_path = substr($request_path, strlen($home_path));
    }
    return trim((string) $request_path, '/');
}

function jazireh_theme_is_react_request()
{
    $react_path = jazireh_theme_request_path();
    if ($react_path === '') {
        return true;
    }
    return (bool) preg_match('#^(news|videos|apod|sky|sky-today|explore|radar|jazireh-daily)(/.*)?$#', $react_path);
}

function jazireh_theme_assets()
{
    if (!jazireh_theme_is_react_request()) {
        return;
    }

    wp_enqueue_style(
        'jazireh-fonts',
        'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800;900&display=swap',
        array(),
        null
    );

    $manifest_path = get_template_directory() . '/dist/.vite/manifest.json';
    if (!file_exists($manifest_path)) {
        return;
    }
    $manifest = json_decode(file_get_contents($manifest_path), true);
    if (!is_array($manifest)) {
        return;
    }
    $entry = null;
    foreach ($manifest as $item) {
        if (!empty($item['isEntry'])) {
            $entry = $item;
            break;
        }
    }
    if (!$entry) {
        return;
    }
    if (!empty($entry['css'])) {
        foreach ($entry['css'] as $index => $css) {
            wp_enqueue_style('jazireh-app-' . $index, get_template_directory_uri() . '/dist/' . ltrim($css, '/'), array(), filemtime(get_template_directory() . '/dist/' . ltrim($css, '/')));
        }
    }
    if (!empty($entry['file'])) {
        wp_enqueue_script('jazireh-app', get_template_directory_uri() . '/dist/' . ltrim($entry['file'], '/'), array(), null, true);
        wp_script_add_data('jazireh-app', 'type', 'module');
    }
}
add_action('wp_enqueue_scripts', 'jazireh_theme_assets');

function jazireh_theme_resource_hints($urls, $relation_type)
{
    if (!jazireh_theme_is_react_request()) {
        return $urls;
    }

    if ($relation_type === 'preconnect') {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = array(
            'href' => 'https://fonts.gstatic.com',
            'crossorigin',
        );
    }

    return $urls;
}
add_filter('wp_resource_hints', 'jazireh_theme_resource_hints', 10, 2);

function jazireh_theme_runtime_config()
{
    if (!jazireh_theme_is_react_request()) {
        return;
    }

    $asset_base = trailingslashit(get_template_directory_uri()) . 'dist';
    $config = array(
        'root' => esc_url_raw(rest_url('jazireh/v1')),
        'siteUrl' => esc_url_raw(home_url('/')),
        'assetBase' => esc_url_raw($asset_base),
    );
    echo '<script>window.JAZIREH_WP=' . wp_json_encode($config) . ';window.JAZIREH_ASSET_BASE=window.JAZIREH_WP.assetBase;</script>';
}
add_action('wp_head', 'jazireh_theme_runtime_config', 1);

function jazireh_theme_brand_meta()
{
    $manifest_url = trailingslashit(get_template_directory_uri()) . 'dist/manifest.webmanifest';
    if (!has_site_icon()) {
        $brand_base = trailingslashit(get_template_directory_uri()) . 'dist/brand/';
        echo '<link rel="icon" href="' . esc_url($brand_base . 'favicon.ico') . '" sizes="any">';
        echo '<link rel="icon" type="image/png" href="' . esc_url($brand_base . 'favicon-64.png') . '">';
        echo '<link rel="apple-touch-icon" href="' . esc_url($brand_base . 'apple-touch-icon.png') . '">';
    }
    echo '<link rel="manifest" href="' . esc_url($manifest_url) . '">';
}
add_action('wp_head', 'jazireh_theme_brand_meta', 2);

function jazireh_theme_rewrites()
{
    add_rewrite_rule('^(news|videos|apod|sky|sky-today|explore|radar|jazireh-daily)(/.*)?$', 'index.php', 'top');
}
add_action('init', 'jazireh_theme_rewrites');

function jazireh_theme_admin_redirects()
{
    $react_path = jazireh_theme_request_path();
    if ($react_path === 'admin') {
        wp_safe_redirect(admin_url(), 302);
        exit;
    }
    if ($react_path === 'admin/login') {
        wp_safe_redirect(wp_login_url(admin_url()), 302);
        exit;
    }
}
add_action('template_redirect', 'jazireh_theme_admin_redirects', 0);

function jazireh_theme_react_route_fallback()
{
    global $wp_query;
    $react_path = jazireh_theme_request_path();
    if (preg_match('#^(news|videos|apod|sky|sky-today|explore|radar|jazireh-daily)(/.*)?$#', $react_path)) {
        if ($wp_query instanceof WP_Query) {
            $wp_query->is_404 = false;
        }
        status_header(200);
        require get_template_directory() . '/index.php';
        exit;
    }
}
add_action('template_redirect', 'jazireh_theme_react_route_fallback', 0);

function jazireh_theme_activate()
{
    jazireh_theme_rewrites();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'jazireh_theme_activate');

function jazireh_theme_module_script($tag, $handle, $src)
{
    if ($handle !== 'jazireh-app') {
        return $tag;
    }
    return '<script type="module" src="' . esc_url($src) . '"></script>';
}
add_filter('script_loader_tag', 'jazireh_theme_module_script', 10, 3);
