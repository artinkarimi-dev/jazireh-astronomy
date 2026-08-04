CREATE DATABASE IF NOT EXISTS jazireh_astronomy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jazireh_astronomy;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS newsletter_subscribers;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS site_settings;
DROP TABLE IF EXISTS celestial_objects;
DROP TABLE IF EXISTS sky_conditions;
DROP TABLE IF EXISTS videos;
DROP TABLE IF EXISTS apod;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS news_categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NULL,
  author_id BIGINT UNSIGNED NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  excerpt TEXT NOT NULL,
  content LONGTEXT NOT NULL,
  image_url VARCHAR(500) NOT NULL,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  reading_time VARCHAR(30) NOT NULL DEFAULT '۵ دقیقه',
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_news_category FOREIGN KEY (category_id) REFERENCES news_categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_news_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_news_public (status, featured, published_at),
  FULLTEXT INDEX ft_news_content (title, excerpt, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apod (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  apod_date DATE NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  image_url VARCHAR(500) NOT NULL,
  media_type ENUM('image','video') NOT NULL DEFAULT 'image',
  explanation LONGTEXT NOT NULL,
  excerpt TEXT NULL,
  credit VARCHAR(255) NULL,
  source_url VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_apod_date (apod_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE videos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  description TEXT NULL,
  video_url VARCHAR(500) NOT NULL,
  poster_url VARCHAR(500) NOT NULL,
  youtube_url VARCHAR(500) NULL,
  duration VARCHAR(30) NULL,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_videos_public (status, featured, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sky_conditions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  location_name VARCHAR(190) NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  temperature DECIMAL(5,2) NOT NULL,
  condition_text VARCHAR(120) NOT NULL,
  humidity TINYINT UNSIGNED NOT NULL,
  wind_speed DECIMAL(6,2) NOT NULL,
  pressure SMALLINT UNSIGNED NOT NULL,
  visibility DECIMAL(5,2) NOT NULL,
  moon_phase VARCHAR(100) NULL,
  moon_illumination DECIMAL(5,2) NULL,
  moon_age DECIMAL(5,2) NULL,
  sunrise VARCHAR(10) NULL,
  sunset VARCHAR(10) NULL,
  best_observation_time VARCHAR(100) NULL,
  seeing_score DECIMAL(3,1) NULL,
  transparency_score DECIMAL(3,1) NULL,
  events_json LONGTEXT NULL,
  observed_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sky_location_time (location_name, observed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE celestial_objects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  name_fa VARCHAR(120) NOT NULL,
  name_en VARCHAR(120) NOT NULL,
  object_type VARCHAR(120) NOT NULL,
  color VARCHAR(20) NOT NULL,
  visual_size DECIMAL(5,2) NOT NULL,
  orbit_distance DECIMAL(6,2) NOT NULL,
  orbit_speed DECIMAL(8,5) NOT NULL,
  has_ring TINYINT(1) NOT NULL DEFAULT 0,
  facts_json LONGTEXT NOT NULL,
  stats_json LONGTEXT NOT NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_objects_active_sort (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE site_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value LONGTEXT NULL,
  value_type ENUM('string','number','boolean','json') NOT NULL DEFAULT 'string',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NULL,
  ip_address VARCHAR(64) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_throttle (ip_address, success, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE newsletter_subscribers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  status ENUM('active','unsubscribed') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_messages_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (name, email, password_hash, role, status) VALUES
('نویسنده نمونه', 'portfolio-author@example.invalid', '$2y$12$2sJpzQiLyLdRkcNEFZ6SiuUT.5oLq4sObWeX12wVRgX5d5ny8aQJy', 'admin', 'inactive');

INSERT INTO news_categories (name, slug) VALUES
('کیهان‌شناسی', 'cosmology'),
('فراخورشیدی', 'exoplanets'),
('ماموریت‌ها', 'missions'),
('منظومه شمسی', 'solar-system'),
('رصد آسمان', 'observing');

INSERT INTO news (category_id, author_id, slug, title, excerpt, content, image_url, status, featured, reading_time, published_at) VALUES
(1, 1, 'webb-distant-galaxy', 'تلسکوپ جیمز وب ساختار یک کهکشان دوردست را با جزئیات تازه ثبت کرد', 'تصاویر تازه، نواحی زایش ستاره‌ای و توزیع غبار را با وضوح بالا نشان می‌دهند.', 'تلسکوپ فضایی جیمز وب با ابزارهای فروسرخ خود ساختارهای ظریفی از غبار و مناطق فعال ستاره‌زایی را ثبت کرده است. این داده‌ها به پژوهشگران کمک می‌کند روند رشد کهکشان‌ها در دوره‌های اولیه کیهان را بهتر بررسی کنند.', '/media/galaxy.jpg', 'published', 1, '۵ دقیقه', NOW()),
(2, 1, 'new-exoplanet', 'سیاره‌ای فراخورشیدی در محدوده قابل سکونت یک ستاره آرام شناسایی شد', 'نامزد تازه جرمی سنگی است و برای بررسی جو احتمالی آن به رصدهای دقیق‌تر نیاز خواهد بود.', 'قرار گرفتن در محدوده قابل سکونت به معنی وجود قطعی آب یا حیات نیست؛ بلکه نشان می‌دهد دمای تعادلی در شرایط مناسب می‌تواند اجازه حضور آب مایع را بدهد.', '/media/hero-planet.jpg', 'published', 1, '۴ دقیقه', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 1, 'lunar-navigation-test', 'آزمایش سامانه ناوبری ماه‌نشین نسل جدید با موفقیت انجام شد', 'سامانه تازه در مرحله فرود نقشه سطح را با داده‌های دوربین تطبیق می‌دهد.', 'ناوبری مبتنی بر تطبیق عوارض سطحی یکی از فناوری‌های کلیدی برای فرود دقیق روی ماه است و به فضاپیما اجازه می‌دهد دهانه‌ها و الگوهای سطحی را شناسایی کند.', '/media/nebula.jpg', 'published', 0, '۳ دقیقه', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 1, 'jupiter-cloud-belts', 'داده‌های تازه از تغییرات کمربندهای ابری مشتری منتشر شد', 'رصدهای چندطول‌موجی تغییراتی را در سرعت باد و ساختار طوفان‌ها نشان می‌دهند.', 'جو مشتری سامانه‌ای پویا از نوارهای روشن و تیره، گردابه‌ها و جریان‌های سریع است. مقایسه تصاویر مرئی و فروسرخ به دانشمندان کمک می‌کند ارتفاع ابرها و حرکت توده‌های جوی را بهتر تخمین بزنند.', '/media/saturn.jpg', 'published', 0, '۶ دقیقه', DATE_SUB(NOW(), INTERVAL 3 DAY));

INSERT INTO apod (apod_date, title, image_url, media_type, explanation, excerpt, credit, source_url) VALUES
(CURDATE(), 'سحابی کارینا؛ زایشگاه ستاره‌ای', '/media/nebula.jpg', 'image', 'نور ستاره‌های جوان دیواره‌های غبار و گاز را روشن کرده و بادهای ستاره‌ای به مرور این ساختارها را می‌تراشند. رنگ‌های تصویر ترکیبی از داده‌های چند فیلتر هستند تا جزئیات علمی بهتر دیده شوند.', 'ستون‌های غبار و گاز در سحابی کارینا، محل شکل‌گیری ستاره‌های جوان و پرانرژی هستند.', 'NASA / ESA / CSA', 'https://apod.nasa.gov/'),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'کهکشان مارپیچی در نور فروسرخ', '/media/galaxy.jpg', 'image', 'نور فروسرخ می‌تواند از بخشی از غبار عبور کند و ساختارهایی را نشان دهد که در نور مرئی کمتر دیده می‌شوند.', 'بازوهای مارپیچی در فروسرخ، نواحی غبارآلود و زایش ستاره‌ای را آشکار می‌کنند.', 'NASA / Webb', 'https://science.nasa.gov/');

INSERT INTO videos (title, slug, description, video_url, poster_url, duration, status, featured, published_at) VALUES
('سفر در امتداد راه شیری', 'milky-way-journey', 'نمایی آرام و سینمایی از ساختار راه شیری و پهنه ستاره‌ای آسمان.', '/media/stars-vertical.mp4', '/media/stars-vertical-poster.jpg', '۱۲:۴۵', 'published', 1, NOW()),
('طلوع زمین از تاریکی فضا', 'earth-sunrise', 'نمایش لبه روشن زمین و جو آبی آن در تاریکی فضا.', '/media/hero-earth.mp4', '/media/hero-earth-poster.jpg', '۱۰:۰۶', 'published', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
('سیاره آبی در خلأ کیهان', 'blue-planet', 'حرکت آرام سیاره در پس‌زمینه‌ای تاریک و مینیمال.', '/media/planet-vertical.mp4', '/media/planet-vertical-poster.jpg', '۱۰:۱۶', 'published', 0, DATE_SUB(NOW(), INTERVAL 2 DAY));

INSERT INTO sky_conditions (location_name, latitude, longitude, temperature, condition_text, humidity, wind_speed, pressure, visibility, moon_phase, moon_illumination, moon_age, sunrise, sunset, best_observation_time, seeing_score, transparency_score, events_json, observed_at) VALUES
('تهران، ایران', 35.6892000, 51.3890000, 24.00, 'آسمان صاف', 32, 8.00, 1016, 9.40, 'هلال افزایشی', 29, 5, '۰۴:۵۶', '۱۹:۳۱', '۲۲:۳۰ تا ۰۳:۳۰', 7.0, 8.5, '[{"title":"هم‌نشینی ماه و زهره","time":"۱۹:۵۰","detail":"فاصله زاویه‌ای تقریبی ۱٫۸ درجه"},{"title":"بارش شهابی اتا دلوی","time":"۰۲:۳۰","detail":"بهترین مشاهده در افق جنوب‌شرقی"},{"title":"عبور ایستگاه فضایی","time":"۲۳:۲۱","detail":"قابل مشاهده برای حدود چهار دقیقه"}]', NOW());

INSERT INTO celestial_objects (slug, name_fa, name_en, object_type, color, visual_size, orbit_distance, orbit_speed, has_ring, facts_json, stats_json, sort_order) VALUES
('mercury', 'عطارد', 'Mercury', 'سیاره سنگی', '#8f8a80', 0.42, 4.80, 0.01500, 0, '["نزدیک‌ترین سیاره به خورشید است.","یک سال عطارد فقط ۸۸ روز زمینی طول می‌کشد.","در دهانه‌های همیشه‌سایه قطبی آن یخ آب دیده شده است."]', '{"diameter":"۴٬۸۸۰ کیلومتر","day":"۵۸٫۶ روز زمینی","year":"۸۸ روز زمینی","moons":"۰"}', 1),
('venus', 'زهره', 'Venus', 'سیاره سنگی', '#d7a86e', 0.62, 6.50, 0.01200, 0, '["چرخش زهره برخلاف بیشتر سیاره‌ها معکوس است.","جو غلیظ آن اثر گلخانه‌ای شدیدی ایجاد می‌کند.","فشار سطحی آن حدود ۹۰ برابر زمین است."]', '{"diameter":"۱۲٬۱۰۴ کیلومتر","day":"۲۴۳ روز زمینی","year":"۲۲۵ روز زمینی","moons":"۰"}', 2),
('earth', 'زمین', 'Earth', 'سیاره اقیانوسی', '#2c7fca', 0.67, 8.40, 0.01000, 0, '["تنها جرم شناخته‌شده با حیات قطعی است.","حدود ۷۱ درصد سطح آن با آب پوشیده شده است.","میدان مغناطیسی زمین بخش زیادی از باد خورشیدی را منحرف می‌کند."]', '{"diameter":"۱۲٬۷۴۲ کیلومتر","day":"۲۳ ساعت و ۵۶ دقیقه","year":"۳۶۵٫۲۵ روز","moons":"۱"}', 3),
('mars', 'مریخ', 'Mars', 'سیاره سنگی', '#b94f35', 0.50, 10.50, 0.00800, 0, '["رنگ سرخ مریخ از اکسید آهن در خاک آن می‌آید.","المپوس مانس بزرگ‌ترین آتشفشان شناخته‌شده منظومه شمسی است.","شواهد فراوانی از جریان آب در گذشته مریخ وجود دارد."]', '{"diameter":"۶٬۷۷۹ کیلومتر","day":"۲۴ ساعت و ۳۷ دقیقه","year":"۶۸۷ روز زمینی","moons":"۲"}', 4),
('jupiter', 'مشتری', 'Jupiter', 'غول گازی', '#c89462', 1.55, 14.50, 0.00450, 0, '["بزرگ‌ترین سیاره منظومه شمسی است.","لکه سرخ بزرگ یک طوفان عظیم و دیرپا است.","میدان مغناطیسی مشتری بسیار قدرتمند است."]', '{"diameter":"۱۳۹٬۸۲۰ کیلومتر","day":"۹ ساعت و ۵۶ دقیقه","year":"۱۱٫۸۶ سال زمینی","moons":"بیش از ۹۰"}', 5),
('saturn', 'زحل', 'Saturn', 'غول گازی حلقه‌دار', '#d6bb82', 1.35, 19.00, 0.00320, 1, '["حلقه‌ها عمدتاً از قطعات یخ و سنگ تشکیل شده‌اند.","چگالی متوسط زحل از آب کمتر است.","قمر تیتان جوی غلیظ و دریاچه‌های هیدروکربنی دارد."]', '{"diameter":"۱۱۶٬۴۶۰ کیلومتر","day":"۱۰ ساعت و ۴۲ دقیقه","year":"۲۹٫۴ سال زمینی","moons":"بیش از ۱۴۰"}', 6),
('uranus', 'اورانوس', 'Uranus', 'غول یخی', '#73c9d6', 0.95, 23.50, 0.00240, 0, '["محور چرخش آن تقریباً روی پهلو قرار دارد.","متان موجود در جو باعث رنگ آبی‌سبز آن می‌شود.","فصل‌های اورانوس چندین دهه طول می‌کشند."]', '{"diameter":"۵۰٬۷۲۴ کیلومتر","day":"۱۷ ساعت و ۱۴ دقیقه","year":"۸۴ سال زمینی","moons":"۲۷"}', 7),
('neptune', 'نپتون', 'Neptune', 'غول یخی', '#315ec9', 0.92, 27.50, 0.00190, 0, '["سریع‌ترین بادهای سیاره‌ای منظومه شمسی در نپتون ثبت شده‌اند.","رنگ آبی آن به ترکیب جو و پراکندگی نور مربوط است.","قمر تریتون در جهتی مخالف چرخش نپتون حرکت می‌کند."]', '{"diameter":"۴۹٬۲۴۴ کیلومتر","day":"۱۶ ساعت","year":"۱۶۴٫۸ سال زمینی","moons":"۱۴"}', 8);

INSERT INTO site_settings (setting_key, setting_value, value_type) VALUES
('site_name', 'جزیره نجوم', 'string'),
('site_tagline', 'کشف کنید، یاد بگیرید، کاوش کنید.', 'string'),
('default_location', '{"name":"تهران، ایران","latitude":35.6892,"longitude":51.3890}', 'json'),
('maintenance_mode', 'false', 'boolean');

SET FOREIGN_KEY_CHECKS = 1;
