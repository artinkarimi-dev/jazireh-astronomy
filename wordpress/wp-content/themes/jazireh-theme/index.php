<!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="root"></div>
<?php if (!file_exists(get_template_directory() . '/dist/.vite/manifest.json')) : ?>
<div style="max-width:760px;margin:80px auto;padding:32px;background:#fff;border-radius:18px;font-family:Tahoma;direction:rtl">
    <h1>فایل‌های React هنوز ساخته نشده‌اند</h1>
    <p>دستور <code>npm run build:wordpress</code> را در پوشه frontend اجرا کنید و پوشه dist را داخل قالب قرار دهید.</p>
</div>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
