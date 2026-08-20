<!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <style>
        body.jazireh-wp-page{margin:0;background:#02040a;color:#e2e8f0;font-family:Tahoma,Arial,sans-serif}
        .jazireh-wp-page-shell{min-height:100vh;padding:48px 20px}
        .jazireh-wp-page-content{max-width:1200px;margin:0 auto}
        .jazireh-wp-page-content a{color:#f6c453}
    </style>
</head>
<body <?php body_class('jazireh-wp-page'); ?>>
<?php wp_body_open(); ?>
<div class="jazireh-wp-page-shell">
    <main class="jazireh-wp-page-content">
        <?php
        while (have_posts()) :
            the_post();
            the_content();
        endwhile;
        ?>
    </main>
</div>
<?php wp_footer(); ?>
</body>
</html>
