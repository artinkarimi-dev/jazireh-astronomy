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
    return (bool) preg_match('#^(news|videos|apod|sky|sky-today|explore|radar|jazireh-daily|search|events|topics|about|contact)(/.*)?$#', $react_path);
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
    $manifest_path = get_template_directory() . '/dist/.vite/manifest.json';
    $build_version = file_exists($manifest_path) ? (string) filemtime($manifest_path) : (string) wp_get_theme()->get('Version');
    $config = array(
        'root' => esc_url_raw(rest_url('jazireh/v1')),
        'siteUrl' => esc_url_raw(home_url('/')),
        'assetBase' => esc_url_raw($asset_base),
        'buildVersion' => sanitize_text_field($build_version),
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

function jazireh_theme_seo_defaults()
{
    return array(
        'site_name' => 'جزیره نجوم',
        'description' => 'جزیره نجوم؛ رسانه‌ای فارسی برای آسمان شب، اخبار علمی، ویدیوهای نجومی و تجربه‌های تعاملی فضایی.',
        'image' => trailingslashit(get_template_directory_uri()) . 'dist/brand/icon-512.png',
        'locale' => 'fa_IR',
        'language' => 'fa-IR'
    );
}

function jazireh_theme_current_url()
{
    $scheme = is_ssl() ? 'https://' : 'http://';
    $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : wp_parse_url(home_url('/'), PHP_URL_HOST);
    $uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
    return esc_url_raw($scheme . $host . $uri);
}

function jazireh_theme_canonical_url()
{
    $path = jazireh_theme_request_path();
    return esc_url_raw(home_url($path === '' ? '/' : '/' . $path . '/'));
}

function jazireh_theme_find_react_post($path, $post_type)
{
    $parts = explode('/', trim($path, '/'));
    $slug = end($parts);
    if (!$slug) {
        return null;
    }
    $post = get_page_by_path(sanitize_title($slug), OBJECT, $post_type);
    return $post instanceof WP_Post && $post->post_status === 'publish' ? $post : null;
}

function jazireh_theme_post_excerpt($post)
{
    return wp_strip_all_tags($post->post_excerpt ?: wp_trim_words($post->post_content, 32, '…'));
}

function jazireh_theme_post_image($post)
{
    $image = get_the_post_thumbnail_url($post->ID, 'large');
    $defaults = jazireh_theme_seo_defaults();
    return $image ? esc_url_raw($image) : $defaults['image'];
}

function jazireh_theme_post_author_name($post)
{
    $author_name = get_the_author_meta('display_name', (int) $post->post_author);
    if ($author_name) {
        return $author_name;
    }
    $defaults = jazireh_theme_seo_defaults();
    return $defaults['site_name'];
}

function jazireh_theme_article_schema($post, $type, $description, $image)
{
    $defaults = jazireh_theme_seo_defaults();
    $base = $post->post_type === 'jazireh_daily' ? 'jazireh-daily' : 'news';
    return array(
        '@context' => 'https://schema.org',
        '@type' => $type,
        'headline' => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
        'description' => $description,
        'image' => array($image),
        'datePublished' => get_post_time(DATE_ATOM, true, $post),
        'dateModified' => get_post_modified_time(DATE_ATOM, true, $post),
        'author' => array('@type' => 'Person', 'name' => jazireh_theme_post_author_name($post)),
        'publisher' => array('@type' => 'Organization', 'name' => $defaults['site_name'], 'logo' => array('@type' => 'ImageObject', 'url' => esc_url_raw($defaults['image']))),
        'mainEntityOfPage' => esc_url_raw(home_url('/' . $base . '/' . $post->post_name . '/'))
    );
}

function jazireh_theme_breadcrumb_schema($title)
{
    $defaults = jazireh_theme_seo_defaults();
    $path = jazireh_theme_request_path();
    $items = array(
        array(
            '@type' => 'ListItem',
            'position' => 1,
            'name' => $defaults['site_name'],
            'item' => esc_url_raw(home_url('/'))
        )
    );
    if ($path !== '') {
        $items[] = array(
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $title,
            'item' => jazireh_theme_canonical_url()
        );
    }
    return array(
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items
    );
}

function jazireh_theme_site_schema()
{
    $defaults = jazireh_theme_seo_defaults();
    $site_url = home_url('/');
    return array(
        'organization' => array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $defaults['site_name'],
            'url' => esc_url_raw($site_url),
            'logo' => esc_url_raw($defaults['image'])
        ),
        'website' => array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $defaults['site_name'],
            'url' => esc_url_raw($site_url),
            'inLanguage' => $defaults['language'],
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => esc_url_raw(home_url('/search?q={search_term_string}')),
                'query-input' => 'required name=search_term_string'
            )
        )
    );
}

function jazireh_theme_route_meta()
{
    $defaults = jazireh_theme_seo_defaults();
    $path = jazireh_theme_request_path();
    $meta = array(
        'title' => $defaults['site_name'],
        'description' => $defaults['description'],
        'type' => 'website',
        'image' => $defaults['image'],
        'robots' => 'index,follow,max-image-preview:large',
        'published_time' => '',
        'modified_time' => '',
        'schema' => array()
    );

    $routes = array(
        '' => array('title' => $defaults['site_name'], 'description' => $defaults['description']),
        'news' => array('title' => 'اخبار علمی', 'description' => 'تازه‌ترین خبرها و روایت‌های فارسی از نجوم، ماموریت‌های فضایی و پژوهش‌های علمی در جزیره.'),
        'videos' => array('title' => 'ویدیوها', 'description' => 'ویدیوهای علمی و نجومی جزیره و دسترسی مستقیم به کانال رسمی یوتیوب.'),
        'apod' => array('title' => 'عکس روز ناسا', 'description' => 'تصویر نجومی روز همراه با روایت و توضیح فارسی در جزیره نجوم.'),
        'topics' => array('title' => 'پرونده‌های علمی', 'description' => 'پرونده‌های موضوعی جزیره برای ماه، خورشید، مریخ، جیمز وب و زمین.'),
        'sky' => array('title' => 'آسمان امروز', 'description' => 'وضعیت رصد آسمان، داده‌های ماه و پنجره پیشنهادی مشاهده برای تهران.'),
        'sky-today' => array('title' => 'آسمان امروز', 'description' => 'وضعیت رصد آسمان، داده‌های ماه و پنجره پیشنهادی مشاهده برای تهران.'),
        'explore' => array('title' => 'کاوش منظومه شمسی', 'description' => 'تجربه تعاملی برای شناخت اجرام آسمانی و داده‌های پایه منظومه شمسی.'),
        'radar' => array('title' => 'رادار آسمان', 'description' => 'نمای تعاملی و آموزشی برای دنبال‌کردن موقعیت اجرام در آسمان.'),
        'jazireh-daily' => array('title' => 'جزیره دیلی', 'description' => 'پست‌های کوتاه علمی و تصویری جزیره از داده‌ها و رسانه‌های روز.'),
        'search' => array('title' => 'جستجوی سایت', 'description' => 'جستجو در اخبار علمی، ویدیوها، تصویر روز ناسا، جزیره دیلی و اجرام آسمانی جزیره.', 'robots' => 'noindex,follow,max-image-preview:large'),
        'events' => array('title' => 'رویدادهای نجومی', 'description' => 'تقویم رویدادهای نجومی پیش‌رو، گرفتگی‌ها، بارش‌های شهابی و پدیده‌های قابل مشاهده در جزیره نجوم.'),
        'about' => array('title' => 'درباره جزیره نجوم', 'description' => 'درباره ماموریت، دامنه نسخه فعلی و رویکرد داده‌ای جزیره نجوم.'),
        'contact' => array('title' => 'تماس با جزیره نجوم', 'description' => 'راه‌های ارتباطی رسمی جزیره نجوم و وضعیت اطلاعات تماس منتشرشده.')
    );

    if (isset($routes[$path])) {
        $meta = array_merge($meta, $routes[$path]);
    } elseif (preg_match('#^topics/([^/]+)$#', $path, $matches)) {
        $topic_meta = jazireh_theme_topic_meta($matches[1]);
        if ($topic_meta) {
            $meta = array_merge($meta, $topic_meta);
        } else {
            $meta['title'] = 'پرونده علمی';
            $meta['description'] = 'پرونده علمی جزیره نجوم.';
        }
    } elseif (preg_match('#^news/([^/]+)$#', $path)) {
        $post = jazireh_theme_find_react_post($path, 'jazireh_news');
        if ($post) {
            $meta['title'] = html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8');
            $meta['description'] = jazireh_theme_post_excerpt($post);
            $meta['type'] = 'article';
            $meta['image'] = jazireh_theme_post_image($post);
            $meta['published_time'] = get_post_time(DATE_ATOM, true, $post);
            $meta['modified_time'] = get_post_modified_time(DATE_ATOM, true, $post);
            $meta['schema'][] = jazireh_theme_article_schema($post, 'NewsArticle', $meta['description'], $meta['image']);
        } else {
            $meta['title'] = 'جزئیات خبر';
            $meta['description'] = 'جزئیات یک خبر علمی در جزیره نجوم.';
        }
    } elseif (preg_match('#^jazireh-daily/([^/]+)$#', $path)) {
        $post = jazireh_theme_find_react_post($path, 'jazireh_daily');
        if ($post) {
            $meta['title'] = html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8');
            $meta['description'] = jazireh_theme_post_excerpt($post);
            $meta['type'] = 'article';
            $meta['image'] = jazireh_theme_post_image($post);
            $meta['published_time'] = get_post_time(DATE_ATOM, true, $post);
            $meta['modified_time'] = get_post_modified_time(DATE_ATOM, true, $post);
            $meta['schema'][] = jazireh_theme_article_schema($post, 'Article', $meta['description'], $meta['image']);
        } else {
            $meta['title'] = 'جزئیات جزیره دیلی';
            $meta['description'] = 'جزئیات پست جزیره دیلی.';
        }
    }

    $site_schema = jazireh_theme_site_schema();
    $meta['schema'][] = jazireh_theme_breadcrumb_schema($meta['title']);
    $meta['schema'] = array_merge(array($site_schema['organization'], $site_schema['website']), $meta['schema']);
    return $meta;
}

function jazireh_theme_topic_meta($slug)
{
    $topics = array(
        'moon' => array('title' => 'پرونده ماه', 'description' => 'فازها، رصد، ماموریت‌ها و داده‌های محاسباتی ماه در جزیره.'),
        'sun' => array('title' => 'پرونده خورشید', 'description' => 'خورشید اکنون، تصویرهای رصدی معتبر و توضیح علمی فعالیت‌های خورشیدی.'),
        'mars' => array('title' => 'پرونده مریخ', 'description' => 'ماموریت‌ها، سطح مریخ، شرایط رصد و خبرهای سیاره سرخ.'),
        'james-webb' => array('title' => 'پرونده تلسکوپ جیمز وب', 'description' => 'رصدهای فروسرخ، کهکشان‌های دور و تصویرهای علمی تلسکوپ فضایی جیمز وب.'),
        'earth' => array('title' => 'پرونده زمین', 'description' => 'زمین از فضا، رخدادهای زمین‌لرزه و ارتباط سیاره ما با رصد آسمان.'),
    );
    $slug = sanitize_title($slug);
    return isset($topics[$slug]) ? $topics[$slug] : null;
}

function jazireh_theme_document_title($title)
{
    if (!jazireh_theme_is_react_request()) {
        return $title;
    }
    $meta = jazireh_theme_route_meta();
    $defaults = jazireh_theme_seo_defaults();
    return $meta['title'] === $defaults['site_name'] ? $defaults['site_name'] : $meta['title'] . ' | ' . $defaults['site_name'];
}
add_filter('pre_get_document_title', 'jazireh_theme_document_title', 20);

function jazireh_theme_output_seo_meta()
{
    if (!jazireh_theme_is_react_request()) {
        return;
    }
    $meta = jazireh_theme_route_meta();
    $full_title = jazireh_theme_document_title($meta['title']);
    $url = jazireh_theme_current_url();
    $defaults = jazireh_theme_seo_defaults();
    echo '<link rel="canonical" href="' . esc_url(jazireh_theme_canonical_url()) . '">' . "\n";
    echo '<meta name="description" content="' . esc_attr($meta['description']) . '">' . "\n";
    echo '<meta name="robots" content="' . esc_attr($meta['robots']) . '">' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr($defaults['locale']) . '">' . "\n";
    echo '<meta property="og:type" content="' . esc_attr($meta['type']) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($full_title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($meta['description']) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($defaults['site_name']) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url($meta['image']) . '">' . "\n";
    if ($meta['type'] === 'article' && $meta['published_time']) {
        echo '<meta property="article:published_time" content="' . esc_attr($meta['published_time']) . '">' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr($meta['modified_time'] ?: $meta['published_time']) . '">' . "\n";
    }
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($full_title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($meta['description']) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url($meta['image']) . '">' . "\n";
    foreach ($meta['schema'] as $schema) {
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
}
add_action('wp_head', 'jazireh_theme_output_seo_meta', 5);

function jazireh_theme_robots($robots)
{
    if (!jazireh_theme_is_react_request()) {
        return $robots;
    }
    $path = jazireh_theme_request_path();
    if ($path === 'search') {
        $robots['noindex'] = true;
        unset($robots['index']);
    } else {
        $robots['index'] = true;
        unset($robots['noindex']);
    }
    $robots['follow'] = true;
    $robots['max-image-preview'] = 'large';
    return $robots;
}
add_filter('wp_robots', 'jazireh_theme_robots', 20);

function jazireh_theme_robots_txt($output, $public)
{
    $output .= "\nSitemap: " . home_url('/jazireh-sitemap.xml') . "\n";
    return $output;
}
add_filter('robots_txt', 'jazireh_theme_robots_txt', 20, 2);

function jazireh_theme_sitemap_urls()
{
    $urls = array(
        home_url('/'),
        home_url('/news/'),
        home_url('/videos/'),
        home_url('/apod/'),
        home_url('/sky/'),
        home_url('/topics/'),
        home_url('/topics/moon/'),
        home_url('/topics/sun/'),
        home_url('/topics/mars/'),
        home_url('/topics/james-webb/'),
        home_url('/topics/earth/'),
        home_url('/explore/'),
        home_url('/radar/'),
        home_url('/jazireh-daily/'),
        home_url('/events/'),
        home_url('/about/'),
        home_url('/contact/')
    );

    foreach (array('jazireh_news' => 'news', 'jazireh_daily' => 'jazireh-daily') as $post_type => $base) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'fields' => 'ids',
            'no_found_rows' => true
        ));
        foreach ($posts as $post_id) {
            $post = get_post($post_id);
            if ($post instanceof WP_Post) {
                $urls[] = home_url('/' . $base . '/' . $post->post_name . '/');
            }
        }
    }

    return array_values(array_unique(array_filter($urls)));
}

function jazireh_theme_sitemap_response()
{
    if (jazireh_theme_request_path() !== 'jazireh-sitemap.xml') {
        return;
    }
    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (jazireh_theme_sitemap_urls() as $url) {
        echo '  <url><loc>' . esc_url($url) . '</loc></url>' . "\n";
    }
    echo '</urlset>';
    exit;
}
add_action('template_redirect', 'jazireh_theme_sitemap_response', 0);

function jazireh_theme_rewrites()
{
    add_rewrite_rule('^jazireh-sitemap\.xml$', 'index.php', 'top');
    add_rewrite_rule('^(news|videos|apod|sky|sky-today|explore|radar|jazireh-daily|search|events|topics|about|contact)(/.*)?$', 'index.php', 'top');
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
    if (preg_match('#^(news|videos|apod|sky|sky-today|explore|radar|jazireh-daily|search|events|topics|about|contact)(/.*)?$#', $react_path)) {
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
