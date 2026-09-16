<?php

declare(strict_types=1);

$wp_load = 'C:/xampp/htdocs/wordpress/wp-load.php';
if (!file_exists($wp_load)) {
    fwrite(STDERR, "wp-load.php not found.\n");
    exit(1);
}

require $wp_load;
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

if (!class_exists('Jazireh_News')) {
    fwrite(STDERR, "Jazireh_News is unavailable.\n");
    exit(1);
}

$author = get_user_by('login', 'Jazireh');
$author_id = $author ? (int) $author->ID : 1;

$stories = array(
    array(
        'slug' => 'webb-ic-348-star-formation',
        'title' => 'تصویر تازه جیمز وب از زایش ستاره‌ها در IC 348',
        'excerpt' => 'تلسکوپ فضایی جیمز وب در یکی از بزرگ‌ترین نماهای منتشرشده خود از IC 348، جزئیاتی از ستاره‌های جوان، کوتوله‌های قهوه‌ای بسیار کم‌جرم و ساختار گاز و غبار این زادگاه ستاره‌ای را آشکار کرده است.',
        'content' => array(
            'جیمز وب این بار به منطقه ستاره‌زایی IC 348 نگاه کرده است؛ خوشه‌ای باز در صورت فلکی برساوش که حدود هزار سال نوری از زمین فاصله دارد. تصویر تازه، میدان گسترده‌ای از ستاره‌های جوان، ابرهای گاز و غبار و جریان‌هایی از ماده را نشان می‌دهد که در محیط پیرامون ستاره‌های در حال شکل‌گیری حرکت می‌کنند.',
            'اهمیت این رصد فقط در زیبایی تصویر نیست. اخترشناسان با داده‌های فروسرخ وب به دنبال کوتوله‌های قهوه‌ای بودند؛ اجرامی که جرمشان از ستاره‌ها کمتر است و همجوشی پایدار هیدروژن در مرکز آن‌ها آغاز نمی‌شود. در این بررسی، جرم برخی از این اجرام تا حدود دو برابر جرم مشتری گزارش شده است؛ محدوده‌ای که مرز میان سیاره‌های غول‌پیکر و کوتوله‌های قهوه‌ای را برای پژوهشگران جذاب‌تر می‌کند.',
            'در تصویر رسمی، رشته‌های گاز و غبار، ستاره‌های جوان و فوران‌های باریک ماده دیده می‌شود. این فوران‌ها می‌توانند هنگام شکل‌گیری ستاره‌ها از سامانه‌های جوان بیرون زده و با محیط اطراف برخورد کنند. بنابراین تصویر وب، هم یک نمای تماشایی از یک پرورشگاه ستاره‌ای است و هم یک ابزار علمی برای بررسی این پرسش که جرم‌های بسیار کوچک در چنین محیط‌هایی چگونه پدید می‌آیند.',
            'این یافته‌ها به معنی پاسخ نهایی به همه پرسش‌های شکل‌گیری ستاره نیست. ناسا این تصویر و توضیح علمی آن را به عنوان داده‌ای تازه از IC 348 منتشر کرده و نتیجه‌گیری دقیق‌تر درباره منشأ اجرام کم‌جرم نیازمند تحلیل‌های ادامه‌دار و مقایسه با مدل‌های شکل‌گیری ستاره و سیاره است.',
            'منبع اصلی این گزارش NASA Science است. متن حاضر بازنویسی تحریریه جزیره نجوم بر پایه گزارش رسمی ناسا است و نقل مستقیم مقاله اصلی نیست.'
        ),
        'category' => array('name' => 'نجوم و فضا', 'slug' => 'space-astronomy'),
        'tags' => array('James Webb', 'IC 348', 'کوتوله قهوه‌ای', 'زایش ستاره‌ها'),
        'source_name' => 'NASA Science',
        'source_url' => 'https://science.nasa.gov/missions/webb/nasas-webb-reveals-dynamic-panorama-of-star-formation/',
        'date' => '2026-09-15 10:00:00',
        'reading_time' => '۴ دقیقه',
        'featured' => '1',
        'image' => 'C:/Users/ASUS/Desktop/STScI-01M0JFFAD7SDCCNHWT7T7P3QX9.png',
        'image_credit' => 'Image: NASA, ESA, CSA, Kevin Luhman (PSU), Catarina Alves de Oliveira (ESA), Mahdi Zamani (ESA/Webb)',
        'image_alt' => 'نمای فروسرخ جیمز وب از منطقه ستاره‌زایی IC 348 با ستاره‌های درخشان، ابرهای گاز و غبار و فوران‌های آبی‌رنگ.'
    ),
    array(
        'slug' => 'quebec-uhackatik-impact-crater',
        'title' => 'دهانه برخوردی تازه در کبک؛ کشفی که از نقشه‌های ماهواره‌ای شروع شد',
        'excerpt' => 'رصد یک ساختار دایره‌ای در نقشه‌های ماهواره‌ای به شناسایی ساختار برخوردی Uhackatik در کبک انجامید؛ تصویری که ناسا منتشر کرده با داده‌های Landsat 8 و تاریخ برداشت ۱۲ اکتبر ۲۰۲۵ ثبت شده است.',
        'content' => array(
            'داستان این کشف از یک برنامه علمی رسمی شروع نشد. بر اساس گزارش NASA Earth Observatory، یک منجم آماتور هنگام بررسی نقشه‌های ماهواره‌ای برای سفر و کمپینگ در کبک، به شکلی دایره‌ای در نزدیکی Lake Marsal برخورد کرد و احتمال داد با ساختاری برخوردی روبه‌رو شده باشد.',
            'تصویر منتشرشده توسط ناسا با ابزار OLI روی ماهواره Landsat 8 ثبت شده و تاریخ برداشت آن ۱۲ اکتبر ۲۰۲۵ است. این تاریخ، زمان ثبت تصویر ماهواره‌ای است و با تاریخ انتشار گزارش ناسا در ۱۵ سپتامبر ۲۰۲۶ یکی نیست؛ تمایزی که در گزارش علمی و رسانه‌ای باید روشن بماند.',
            'در نمای ماهواره‌ای، اثر دایره‌ای Uhackatik ظریف است و Lake Marsal نزدیک مرکز آن دیده می‌شود. پژوهشگران زمین‌شناسی برای تأیید منشأ برخوردی چنین ساختارهایی به شواهد میدانی و معیارهای تشخیصی نیاز دارند؛ بنابراین مشاهده یک حلقه در تصویر، به تنهایی برای اعلام قطعی یک دهانه کافی نیست.',
            'گزارش ناسا این رویداد را نمونه‌ای از پیوند داده‌های آزاد ماهواره‌ای، کنجکاوی عمومی و بررسی تخصصی زمین‌شناسان معرفی می‌کند. این کشف همچنین یادآوری می‌کند که روی زمین هنوز ساختارهای برخوردی ناشناخته‌ای می‌توانند زیر فرسایش، پوشش گیاهی یا چشم‌اندازهای پیچیده پنهان مانده باشند.',
            'منبع اصلی این گزارش NASA Earth Observatory است. متن حاضر بازنویسی تحریریه جزیره نجوم بر پایه گزارش رسمی ناسا است.'
        ),
        'category' => array('name' => 'زمین و طبیعت', 'slug' => 'earth-nature'),
        'tags' => array('Landsat 8', 'دهانه برخوردی', 'کبک', 'زمین از فضا'),
        'source_name' => 'NASA Earth Observatory',
        'source_url' => 'https://science.nasa.gov/earth/earth-observatory/an-accidental-impact-crater-discovery/',
        'date' => '2026-09-15 09:00:00',
        'reading_time' => '۴ دقیقه',
        'featured' => '0',
        'image' => 'C:/Users/ASUS/Desktop/newcraterquebec_oli_20251012_lrg.jpg',
        'image_credit' => 'NASA Earth Observatory image by Lauren Dauphin, using Landsat data from the U.S. Geological Survey',
        'image_alt' => 'نمای ماهواره‌ای Landsat از چشم‌انداز کبک که ساختار دایره‌ای Uhackatik و Lake Marsal را در میان زمین‌های سبز و سنگی نشان می‌دهد.'
    ),
    array(
        'slug' => 'neuroprosthesis-paralysis-speech-gesture',
        'title' => 'رابط مغز و رایانه که گفتار و حرکت بدن را هم‌زمان بازسازی می‌کند',
        'excerpt' => 'گزارش NIH از یک پژوهش Nature Neuroscience نشان می‌دهد سامانه‌ای آزمایشی توانسته سیگنال‌های مغزی مرتبط با گفتار و ژست‌های بدنی را برای یک آواتار دیجیتال رمزگشایی کند؛ اما این هنوز درمان یا کاربرد عمومی نیست.',
        'content' => array(
            'پژوهش تازه‌ای که NIH درباره آن گزارش داده، روی یکی از دشوارترین جنبه‌های ارتباط انسانی تمرکز دارد: هم‌زمانی گفتار و زبان بدن. در این مطالعه، پژوهشگران با استفاده از آرایه‌های ECoG و مدل‌های یادگیری ماشین، تلاش کردند فعالیت مغزی مرتبط با گفتار و حرکت‌های بالاتنه را به بیان دیجیتال در یک آواتار تبدیل کنند.',
            'گزارش NIH تأکید می‌کند که رابط‌های مغز و رایانه پیش‌تر توانسته‌اند برخی شکل‌های گفتار یا حرکت را جداگانه پشتیبانی کنند، اما بازسازی هم‌زمان گفتار و ژست‌ها مسئله‌ای پیچیده‌تر است. در این آزمایش، داده‌ها هنگام تلاش شرکت‌کنندگان برای گفتن عبارت‌ها یا انجام ژست‌هایی مانند تکان دادن دست و علامت مثبت گردآوری شد.',
            'این نتیجه از نظر علمی مهم است، چون ارتباط انسانی فقط واژه‌ها نیست. حالت بدن، حرکت دست و ریتم بیان، بخش مهمی از انتقال معنا و حضور اجتماعی را می‌سازند. با این حال، نباید یافته را بزرگ‌تر از محدوده پژوهش تفسیر کرد: این یک درمان برای فلج نیست، یک محصول آماده مصرف نیست و برای کاربرد بلندمدت هنوز به آزمون‌های بیشتر، سامانه‌های ایمن‌تر و بررسی‌های بالینی نیاز دارد.',
            'NIH گزارش داده است که پژوهشگران در آینده نسخه‌ای کاملاً کاشتنی و بی‌سیم را آزمایش خواهند کرد. تا آن زمان، ارزش اصلی این کار در نشان دادن امکان علمی بازسازی چندوجهی ارتباط است، نه وعده قطعی برای بازگشت کامل توان ارتباطی همه بیماران.',
            'منبع اصلی این گزارش NIH است و مقاله پژوهشی مرتبط در Nature Neuroscience منتشر شده است. متن حاضر بازنویسی تحریریه جزیره نجوم بر پایه گزارش رسمی NIH است.'
        ),
        'category' => array('name' => 'زیست‌شناسی و انسان', 'slug' => 'biology-human'),
        'tags' => array('NIH', 'رابط مغز و رایانه', 'فلج', 'Nature Neuroscience'),
        'source_name' => 'National Institutes of Health',
        'source_url' => 'https://www.nih.gov/news-events/news-releases/neuroprosthesis-paralysis-enables-simultaneous-speech-body-language',
        'date' => '2026-09-14 12:00:00',
        'reading_time' => '۴ دقیقه',
        'featured' => '0',
        'image' => 'C:/Users/ASUS/Desktop/20260914-nidcd.png',
        'image_credit' => 'Chang Lab, UCSF',
        'image_alt' => 'نمایش آواتار دیجیتال روی نمایشگر که ژست و گفتار رمزگشایی‌شده از فعالیت مغزی را برای یک شرکت‌کننده در پژوهش نشان می‌دهد.'
    ),
    array(
        'slug' => 'roman-fuel-savings-potential-lifetime',
        'title' => 'صرفه‌جویی سوخت، عمر بالقوه مأموریت رومن را افزایش می‌دهد',
        'excerpt' => 'ناسا می‌گوید دقت اصلاح مسیر نخست و ذخیره سوخت رصدخانه فضایی نانسی گریس رومن می‌تواند عمر عملیاتی بالقوه مأموریت را به دست‌کم ۲۲ سال برساند؛ عددی که تضمین مدت مأموریت نیست.',
        'content' => array(
            'وبلاگ مأموریت رومن ناسا گزارش داده است که اصلاح مسیر نخست رصدخانه فضایی نانسی گریس رومن با دقت بسیار بالا انجام شده و سوخت بسیار کمتری از مقدار بودجه‌بندی‌شده مصرف کرده است. همین صرفه‌جویی، همراه با سوخت اضافه هنگام پرتاب و پیش‌بینی مانورهای بعدی، چشم‌انداز عمر عملیاتی بالقوه مأموریت را به شکل چشمگیری افزایش داده است.',
            'رومن برای یک مأموریت اصلی پنج‌ساله و امکان مأموریت تمدیدشده پنج‌ساله طراحی شده بود؛ یعنی بودجه سوختی در حدود ده سال عملیات. ناسا اکنون می‌گوید بر پایه برآوردهای فعلی، سوخت کافی برای دست‌کم ۲۲ سال عملیات علمی بالقوه وجود دارد. واژه «بالقوه» در اینجا مهم است: این گزارش به معنای تضمین رسمی ۲۲ سال فعالیت نیست.',
            'طبق گزارش ناسا، مانور ۳۱ اوت با دقت بیش از ۹۹ درصد اجرا شد و به جای مصرف سوختی نزدیک به ۲۰۰ کیلوگرم، حدود ۱۸ کیلوگرم سوخت نیاز داشت. همچنین جرم واقعی فضاپیما کمتر از مقدار محافظه‌کارانه‌ای بود که در طراحی بودجه سوخت لحاظ شده بود و این موضوع امکان پر کردن بیشتر مخازن را فراهم کرد.',
            'اگر رومن پس از مانورهای باقی‌مانده با موفقیت در مدار خود پیرامون L2 مستقر شود، سوخت عمدتاً برای مانورهای نگهداری مدار استفاده خواهد شد. افزایش عمر بالقوه می‌تواند فرصت بیشتری برای نقشه‌برداری‌های گسترده، مطالعه ماده و انرژی تاریک، سیاره‌های فراخورشیدی و اخترفیزیک فروسرخ فراهم کند.',
            'منبع اصلی این گزارش وبلاگ رسمی مأموریت Roman در NASA Science است. متن حاضر بازنویسی تحریریه جزیره نجوم بر پایه گزارش رسمی ناسا است.'
        ),
        'category' => array('name' => 'فناوری', 'slug' => 'technology'),
        'tags' => array('Roman Space Telescope', 'L2', 'مأموریت فضایی', 'ناسا'),
        'source_name' => 'NASA Science / Roman Mission Blog',
        'source_url' => 'https://science.nasa.gov/blogs/roman/2026/09/14/fuel-savings-double-potential-lifetime-for-nasas-roman-mission/',
        'date' => '2026-09-14 11:00:00',
        'reading_time' => '۴ دقیقه',
        'featured' => '0',
        'image' => 'C:/Users/ASUS/Desktop/Roman-1.png',
        'image_credit' => 'NASA',
        'image_alt' => 'تصویر گرافیکی رسمی از تلسکوپ فضایی نانسی گریس رومن با بدنه و آینه‌های آبی‌رنگ.'
    ),
    array(
        'slug' => 'hubble-webb-trans-neptunian-objects',
        'title' => 'هابل و وب از اجرام دوردست منظومه شمسی سرنخ‌های گذشته را می‌خوانند',
        'excerpt' => 'رصدهای مشترک هابل و جیمز وب از ۲۷ جرم کم‌نور فرا نپتونی نشان می‌دهد رنگ و ترکیب این اجرام کوچک می‌تواند رابطه‌هایی شبیه اعضای بزرگ‌تر همان خانواده‌ها داشته باشد.',
        'content' => array(
            'ناسا گزارش داده است که پژوهشگران برای نخستین بار از توان مشترک هابل و جیمز وب برای مطالعه گروهی از اجرام بسیار کم‌نور فرا نپتونی استفاده کرده‌اند؛ اجرامی کوچک و یخی که فراتر از مدار نپتون به دور خورشید می‌گردند و با چشم غیرمسلح به هیچ وجه قابل مشاهده نیستند.',
            'در متن‌های انگلیسی به این اجرام Trans-Neptunian Objects یا به اختصار TNO گفته می‌شود. در این بررسی، داده‌های Hubble و Webb کنار هم استفاده شده‌اند تا ویژگی‌های سطحی اجرام کم‌نورتر بهتر سنجیده شود.',
            'در این پژوهش، ۲۷ جرم کوچک و تازه کشف‌شده بررسی شده‌اند. هابل نور مرئی و وب نور فروسرخ را برای همان اجرام اندازه‌گیری کرد تا پژوهشگران بتوانند رنگ، ترکیب سطحی، اندازه و مدار آن‌ها را بهتر تحلیل کنند. یکی از نتیجه‌های مهم گزارش ناسا این است که شمار اجرام کوچک کمتر از مقداری بوده که برخی مدل‌های شکل‌گیری سیاره‌ها انتظار داشتند.',
            'پژوهشگران همچنین دیده‌اند که رنگ‌ها و ویژگی‌های سطحی این اجرام کوچک با روابطی شبیه اعضای بزرگ‌تر خانواده‌های فرا نپتونی همراه است. این موضوع می‌تواند نشان دهد که برخی از این اجرام، با وجود گذشت زمان و برخوردهای احتمالی، هنوز نشانه‌هایی از محیط شکل‌گیری اولیه خود را حفظ کرده‌اند.',
            'با این حال، تعبیر «به یاد داشتن گذشته» یک بیان علمی-رسانه‌ای برای حفظ نشانه‌های اولیه است، نه حافظه واقعی. ناسا نیز این یافته‌ها را در چارچوب پرسش‌های باز درباره شکل‌گیری سیاره‌واره‌ها و تاریخ آغازین منظومه شمسی توضیح می‌دهد.',
            'منبع اصلی این گزارش NASA Science است. تصویر شاخص، طرح هنری رسمی یک جرم فرا نپتونی است و باید به عنوان طرح مفهومی خوانده شود، نه عکس مستقیم از یکی از اجرام مطالعه‌شده.'
        ),
        'category' => array('name' => 'نجوم و فضا', 'slug' => 'space-astronomy'),
        'tags' => array('Hubble', 'James Webb', 'TNO', 'منظومه شمسی'),
        'source_name' => 'NASA Science',
        'source_url' => 'https://science.nasa.gov/missions/hubble/nasas-hubble-webb-find-far-out-solar-system-objects-remember-past/',
        'date' => '2026-09-08 10:00:00',
        'reading_time' => '۴ دقیقه',
        'featured' => '0',
        'image' => 'C:/Users/ASUS/Desktop/STScI-01KZC81GGE6DAJKS1C8KAXHZ2M.jpg',
        'image_credit' => 'Artwork: NASA, ESA, Leah Hustak (STScI)',
        'image_alt' => 'طرح هنری یک جرم فرا نپتونی قهوه‌ای‌رنگ در برابر پس‌زمینه تاریک فضا با برچسب Artist’s Concept.'
    ),
);

function jazireh_phase1_term_id(array $category): int
{
    $term = term_exists($category['slug'], Jazireh_News::TAXONOMY);
    if (!$term) {
        $term = wp_insert_term($category['name'], Jazireh_News::TAXONOMY, array('slug' => $category['slug']));
    }
    if (is_wp_error($term)) {
        throw new RuntimeException($term->get_error_message());
    }
    return (int) (is_array($term) ? $term['term_id'] : $term);
}

function jazireh_phase1_import_image(array $story, int $post_id): int
{
    $existing = get_post_thumbnail_id($post_id);
    if ($existing) {
        update_post_meta($existing, '_wp_attachment_image_alt', $story['image_alt']);
        return (int) $existing;
    }

    if (!file_exists($story['image'])) {
        throw new RuntimeException('Image missing: ' . $story['image']);
    }

    $uploads = wp_upload_dir();
    if (!empty($uploads['error'])) {
        throw new RuntimeException($uploads['error']);
    }

    $filename = sanitize_file_name($story['slug'] . '-' . basename($story['image']));
    $target = trailingslashit($uploads['path']) . $filename;
    if (!copy($story['image'], $target)) {
        throw new RuntimeException('Could not copy image for ' . $story['slug']);
    }

    $attachment_id = wp_insert_attachment(array(
        'post_mime_type' => wp_check_filetype($target)['type'],
        'post_title' => $story['title'],
        'post_content' => $story['image_credit'],
        'post_excerpt' => $story['image_credit'],
        'post_status' => 'inherit',
    ), $target, $post_id);

    if (is_wp_error($attachment_id)) {
        throw new RuntimeException($attachment_id->get_error_message());
    }

    wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $target));
    update_post_meta($attachment_id, '_wp_attachment_image_alt', $story['image_alt']);
    set_post_thumbnail($post_id, $attachment_id);
    return (int) $attachment_id;
}

$published = array();
foreach ($stories as $story) {
    $term_id = jazireh_phase1_term_id($story['category']);
    $content = '';
    foreach ($story['content'] as $paragraph) {
        $content .= '<p>' . esc_html($paragraph) . '</p>' . "\n";
    }
    $content .= '<h2>منبع و اعتبار</h2>' . "\n";
    $content .= '<p>منبع اصلی: <a href="' . esc_url($story['source_url']) . '" rel="nofollow noopener" target="_blank">' . esc_html($story['source_name']) . '</a></p>' . "\n";
    $content .= '<p>اعتبار تصویر: ' . esc_html($story['image_credit']) . '</p>' . "\n";

    $existing = get_page_by_path($story['slug'], OBJECT, Jazireh_News::POST_TYPE);
    $post_data = array(
        'post_type' => Jazireh_News::POST_TYPE,
        'post_status' => 'publish',
        'post_author' => $author_id,
        'post_name' => $story['slug'],
        'post_title' => $story['title'],
        'post_excerpt' => $story['excerpt'],
        'post_content' => $content,
        'post_date' => $story['date'],
        'post_date_gmt' => get_gmt_from_date($story['date']),
    );
    if ($existing) {
        $post_data['ID'] = $existing->ID;
        $post_id = wp_update_post($post_data, true);
    } else {
        $post_id = wp_insert_post($post_data, true);
    }
    if (is_wp_error($post_id)) {
        throw new RuntimeException($post_id->get_error_message());
    }

    wp_set_object_terms($post_id, array($term_id), Jazireh_News::TAXONOMY);
    wp_set_object_terms($post_id, $story['tags'], 'post_tag');
    update_post_meta($post_id, Jazireh_News::META_READING_TIME, $story['reading_time']);
    update_post_meta($post_id, Jazireh_News::META_FEATURED, $story['featured']);
    update_post_meta($post_id, Jazireh_News::META_SOURCE_NAME, $story['source_name']);
    update_post_meta($post_id, Jazireh_News::META_SOURCE_URL, $story['source_url']);
    update_post_meta($post_id, Jazireh_News::META_TRANSLATOR, 'تحریریه جزیره نجوم');
    update_post_meta($post_id, Jazireh_News::META_IMAGE_CREDIT, $story['image_credit']);
    $attachment_id = jazireh_phase1_import_image($story, (int) $post_id);

    $published[] = array(
        'id' => (int) $post_id,
        'slug' => $story['slug'],
        'title' => $story['title'],
        'category' => $story['category']['name'],
        'attachment' => $attachment_id,
    );
}

$featured = get_page_by_path('webb-ic-348-star-formation', OBJECT, Jazireh_News::POST_TYPE);
if ($featured) {
    $settings = get_option('jazireh_settings', array());
    if (!is_array($settings)) {
        $settings = array();
    }
    if (empty($settings['homepage']) || !is_array($settings['homepage'])) {
        $settings['homepage'] = array();
    }
    $settings['homepage']['featured_news_ids'] = array((int) $featured->ID);
    update_option('jazireh_settings', $settings, false);
}

echo wp_json_encode($published, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
