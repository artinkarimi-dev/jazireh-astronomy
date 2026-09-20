<?php
/*
Plugin Name: Jazireh Core
Description: هسته اختصاصی مدیریت محتوا و REST API جزیره نجوم
Version: 1.0.0
Author: Artin Karimi
Requires at least: 6.4
Requires PHP: 7.4
Text Domain: jazireh-core
*/

if (!defined('ABSPATH')) {
    exit;
}

define('JAZIREH_CORE_VERSION', '1.0.0');
define('JAZIREH_CORE_PATH', plugin_dir_path(__FILE__));

require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-news.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-objects.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-newsletter.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-youtube.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-daily.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-settings.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-astronomy-cache.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-visibility-service.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-horizons-provider.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-ephemeris.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-astronomy.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-planets.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-events.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-topics.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-sun.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-apod-editorial.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-apod-localizer.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-apod-service.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-sun-service.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-moon-service.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-sky-service.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-earth-service.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-earthquake-service.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-widgets.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-search.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-rest.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-admin.php';

function jazireh_core_boot()
{
    Jazireh_News::boot();
    Jazireh_Objects::boot();
    Jazireh_Newsletter::boot();
    Jazireh_YouTube::boot();
    Jazireh_Daily::boot();
    Jazireh_Events::boot();
    Jazireh_Topics::boot();
    Jazireh_APOD_Editorial::boot();
    Jazireh_APOD_Localizer::boot();
    Jazireh_APOD_Service::boot();
    Jazireh_Sun_Service::boot();
    Jazireh_Settings::boot();
    Jazireh_REST::boot();
    Jazireh_Admin::boot();
}
add_action('plugins_loaded', 'jazireh_core_boot');

function jazireh_core_send_base_security_headers()
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(self), camera=(), microphone=(), payment=(), usb=(), interest-cohort=()');
    header('X-Frame-Options: SAMEORIGIN');

    if (is_ssl() && defined('JAZIREH_ENABLE_HSTS') && JAZIREH_ENABLE_HSTS) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
add_action('send_headers', 'jazireh_core_send_base_security_headers', 5);
add_action('login_init', 'jazireh_core_send_base_security_headers', 5);
add_filter('rest_pre_serve_request', function ($served) {
    jazireh_core_send_base_security_headers();
    return $served;
}, 5);

function jazireh_core_activate()
{
    Jazireh_News::register_content_types();
    Jazireh_Objects::register_content_type();
    Jazireh_Daily::register_content_type();
    Jazireh_Events::register_content_type();
    Jazireh_Topics::register_taxonomy();
    Jazireh_APOD_Editorial::register_content_type();
    Jazireh_News::seed_content();
    Jazireh_Newsletter::maybe_upgrade_schema();
    Jazireh_APOD_Service::schedule_refresh();
    Jazireh_Sun_Service::maybe_schedule_refresh();
    Jazireh_Settings::schedule_widget_prewarm();
    Jazireh_YouTube::maybe_schedule_refresh();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'jazireh_core_activate');

function jazireh_core_deactivate()
{
    Jazireh_APOD_Localizer::clear_schedule();
    Jazireh_APOD_Service::clear_schedule();
    Jazireh_Settings::clear_widget_prewarm();
    Jazireh_Sun_Service::clear_schedule();
    Jazireh_YouTube::clear_schedule();
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'jazireh_core_deactivate');
