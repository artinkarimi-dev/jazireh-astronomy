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
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-rest.php';
require_once JAZIREH_CORE_PATH . 'includes/class-jazireh-admin.php';

function jazireh_core_boot()
{
    Jazireh_News::boot();
    Jazireh_Objects::boot();
    Jazireh_Newsletter::boot();
    Jazireh_YouTube::boot();
    Jazireh_Daily::boot();
    Jazireh_Settings::boot();
    Jazireh_REST::boot();
    Jazireh_Admin::boot();
}
add_action('plugins_loaded', 'jazireh_core_boot');

function jazireh_core_activate()
{
    Jazireh_News::register_content_types();
    Jazireh_Objects::register_content_type();
    Jazireh_Daily::register_content_type();
    Jazireh_News::seed_content();
    Jazireh_Newsletter::maybe_upgrade_schema();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'jazireh_core_activate');

function jazireh_core_deactivate()
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'jazireh_core_deactivate');
