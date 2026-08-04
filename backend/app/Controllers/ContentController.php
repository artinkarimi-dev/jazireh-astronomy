<?php

class ContentController
{
    public function health()
    {
        Database::connection()->query('SELECT 1');
        Response::success(array('service' => 'jazireh-api', 'time' => date(DATE_ATOM)), 'API فعال است.');
    }

    public function home()
    {
        $pdo = Database::connection();
        $news = $pdo->query("SELECT n.id, n.slug, n.title, n.excerpt, n.image_url AS image, n.published_at AS publishedAt, c.name AS category FROM news n LEFT JOIN news_categories c ON c.id = n.category_id WHERE n.status = 'published' ORDER BY n.featured DESC, n.published_at DESC LIMIT 3")->fetchAll();
        $apod = $pdo->query("SELECT id, title, image_url AS image, explanation AS content, credit AS photographer, apod_date AS date FROM apod ORDER BY apod_date DESC LIMIT 1")->fetch();
        $videos = $pdo->query("SELECT id, title, description, video_url AS source, poster_url AS poster, duration FROM videos WHERE status = 'published' ORDER BY published_at DESC LIMIT 3")->fetchAll();
        $sky = $pdo->query("SELECT location_name AS location, temperature, condition_text AS `condition`, humidity, wind_speed AS wind, pressure, visibility, moon_phase AS moonPhase, moon_illumination AS moonIllumination, sunrise, sunset, best_observation_time AS bestTime, observed_at FROM sky_conditions ORDER BY observed_at DESC LIMIT 1")->fetch();
        Response::success(array('news' => $news, 'apod' => $apod, 'videos' => $videos, 'sky' => $sky));
    }

    public function apod()
    {
        $limit = min(max((int) Request::query('limit', 20), 1), 100);
        $stmt = Database::connection()->query("SELECT id, apod_date AS date, title, image_url AS image, media_type AS mediaType, explanation AS content, excerpt, credit AS photographer, source_url AS sourceUrl FROM apod ORDER BY apod_date DESC LIMIT {$limit}");
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
        }
        Response::success($rows);
    }

    public function apodToday()
    {
        $row = Database::connection()->query("SELECT id, apod_date AS date, title, image_url AS image, media_type AS mediaType, explanation AS content, excerpt, credit AS photographer, source_url AS sourceUrl FROM apod ORDER BY apod_date DESC LIMIT 1")->fetch();
        if (!$row) {
            Response::error('تصویر روز ثبت نشده است.', 404);
        }
        $row['id'] = (int) $row['id'];
        Response::success($row);
    }

    public function videos()
    {
        $stmt = Database::connection()->query("SELECT id, title, slug, description, video_url AS source, poster_url AS poster, youtube_url AS youtubeUrl, duration, published_at AS publishedAt FROM videos WHERE status = 'published' ORDER BY featured DESC, published_at DESC");
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
        }
        Response::success($rows);
    }

    public function skyToday()
    {
        $stmt = Database::connection()->query("SELECT id, location_name AS location, latitude, longitude, temperature, condition_text AS `condition`, humidity, wind_speed AS wind, pressure, visibility, moon_phase AS moonPhase, moon_illumination AS moonIllumination, moon_age AS moonAge, sunrise, sunset, best_observation_time AS bestTime, seeing_score AS seeing, transparency_score AS transparency, events_json AS events, observed_at FROM sky_conditions ORDER BY observed_at DESC LIMIT 1");
        $row = $stmt->fetch();
        if (!$row) {
            Response::error('داده آسمان موجود نیست.', 404);
        }
        $row['id'] = (int) $row['id'];
        $row['temperature'] = (float) $row['temperature'];
        $row['humidity'] = (int) $row['humidity'];
        $row['events'] = json_decode($row['events'], true) ?: array();
        Response::success($row);
    }

    public function objects()
    {
        $stmt = Database::connection()->query("SELECT id, slug, name_fa AS name, name_en AS nameEn, object_type AS type, color, visual_size AS size, orbit_distance AS distance, orbit_speed AS speed, has_ring AS ring, facts_json AS facts, stats_json AS stats FROM celestial_objects WHERE status = 'active' ORDER BY sort_order ASC");
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['size'] = (float) $row['size'];
            $row['distance'] = (float) $row['distance'];
            $row['speed'] = (float) $row['speed'];
            $row['ring'] = (bool) $row['ring'];
            $row['facts'] = json_decode($row['facts'], true) ?: array();
            $row['stats'] = json_decode($row['stats'], true) ?: array();
        }
        Response::success($rows);
    }

    public function object($params)
    {
        $stmt = Database::connection()->prepare("SELECT id, slug, name_fa AS name, name_en AS nameEn, object_type AS type, color, visual_size AS size, orbit_distance AS distance, orbit_speed AS speed, has_ring AS ring, facts_json AS facts, stats_json AS stats FROM celestial_objects WHERE slug = ? AND status = 'active' LIMIT 1");
        $stmt->execute(array($params['slug']));
        $row = $stmt->fetch();
        if (!$row) {
            Response::error('جرم آسمانی پیدا نشد.', 404);
        }
        $row['facts'] = json_decode($row['facts'], true) ?: array();
        $row['stats'] = json_decode($row['stats'], true) ?: array();
        Response::success($row);
    }

    public function newsletter()
    {
        $data = Request::json();
        if (!isset($data['email']) || !Validator::email($data['email'])) {
            Response::error('ایمیل معتبر وارد کنید.', 422);
        }
        $stmt = Database::connection()->prepare("INSERT INTO newsletter_subscribers (email, status, created_at) VALUES (?, 'active', NOW()) ON DUPLICATE KEY UPDATE status = 'active', updated_at = NOW()");
        $stmt->execute(array(strtolower(trim($data['email']))));
        Response::success(null, 'عضویت شما ثبت شد.', 201);
    }
}
