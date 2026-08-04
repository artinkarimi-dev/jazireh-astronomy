<?php

class AdminContentController
{
    public function storeApod()
    {
        $data = Request::json();
        $errors = Validator::required($data, array('date', 'title', 'image', 'content'));
        if (!empty($errors)) {
            Response::error('اطلاعات تصویر روز کامل نیست.', 422, $errors);
        }
        $stmt = Database::connection()->prepare("INSERT INTO apod (apod_date, title, image_url, media_type, explanation, excerpt, credit, source_url, created_at, updated_at) VALUES (?, ?, ?, 'image', ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute(array($data['date'], Validator::clean($data['title']), Validator::clean($data['image']), trim($data['content']), isset($data['excerpt']) ? Validator::clean($data['excerpt']) : '', isset($data['photographer']) ? Validator::clean($data['photographer']) : '', isset($data['sourceUrl']) ? Validator::clean($data['sourceUrl']) : ''));
        Response::success(array('id' => (int) Database::connection()->lastInsertId()), 'تصویر روز ثبت شد.', 201);
    }

    public function updateApod($params)
    {
        $data = Request::json();
        $fields = array();
        $values = array();
        $map = array('date' => 'apod_date', 'title' => 'title', 'image' => 'image_url', 'content' => 'explanation', 'excerpt' => 'excerpt', 'photographer' => 'credit', 'sourceUrl' => 'source_url');
        foreach ($map as $key => $column) {
            if (isset($data[$key])) {
                $fields[] = $column . ' = ?';
                $values[] = $key === 'content' ? trim($data[$key]) : Validator::clean($data[$key]);
            }
        }
        if (empty($fields)) {
            Response::error('داده‌ای برای ویرایش ارسال نشده است.', 422);
        }
        $values[] = (int) $params['id'];
        $stmt = Database::connection()->prepare('UPDATE apod SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ?');
        $stmt->execute($values);
        Response::success(null, 'تصویر روز ویرایش شد.');
    }

    public function deleteApod($params)
    {
        $stmt = Database::connection()->prepare('DELETE FROM apod WHERE id = ?');
        $stmt->execute(array((int) $params['id']));
        Response::success(null, 'تصویر روز حذف شد.');
    }

    public function storeVideo()
    {
        $data = Request::json();
        $errors = Validator::required($data, array('title', 'slug', 'source', 'poster'));
        if (!empty($errors)) {
            Response::error('اطلاعات ویدیو کامل نیست.', 422, $errors);
        }
        $stmt = Database::connection()->prepare("INSERT INTO videos (title, slug, description, video_url, poster_url, youtube_url, duration, status, featured, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'published', ?, NOW(), NOW(), NOW())");
        $stmt->execute(array(Validator::clean($data['title']), strtolower(Validator::clean($data['slug'])), isset($data['description']) ? Validator::clean($data['description']) : '', Validator::clean($data['source']), Validator::clean($data['poster']), isset($data['youtubeUrl']) ? Validator::clean($data['youtubeUrl']) : '', isset($data['duration']) ? Validator::clean($data['duration']) : '', !empty($data['featured']) ? 1 : 0));
        Response::success(array('id' => (int) Database::connection()->lastInsertId()), 'ویدیو ثبت شد.', 201);
    }

    public function updateVideo($params)
    {
        $data = Request::json();
        $fields = array();
        $values = array();
        $map = array('title' => 'title', 'slug' => 'slug', 'description' => 'description', 'source' => 'video_url', 'poster' => 'poster_url', 'youtubeUrl' => 'youtube_url', 'duration' => 'duration', 'status' => 'status');
        foreach ($map as $key => $column) {
            if (isset($data[$key])) {
                $fields[] = $column . ' = ?';
                $values[] = Validator::clean($data[$key]);
            }
        }
        if (isset($data['featured'])) {
            $fields[] = 'featured = ?';
            $values[] = !empty($data['featured']) ? 1 : 0;
        }
        if (empty($fields)) {
            Response::error('داده‌ای برای ویرایش ارسال نشده است.', 422);
        }
        $values[] = (int) $params['id'];
        $stmt = Database::connection()->prepare('UPDATE videos SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ?');
        $stmt->execute($values);
        Response::success(null, 'ویدیو ویرایش شد.');
    }

    public function deleteVideo($params)
    {
        $stmt = Database::connection()->prepare('DELETE FROM videos WHERE id = ?');
        $stmt->execute(array((int) $params['id']));
        Response::success(null, 'ویدیو حذف شد.');
    }

    public function updateSky()
    {
        $data = Request::json();
        $errors = Validator::required($data, array('location', 'temperature', 'condition'));
        if (!empty($errors)) {
            Response::error('اطلاعات آسمان کامل نیست.', 422, $errors);
        }
        $stmt = Database::connection()->prepare('INSERT INTO sky_conditions (location_name, latitude, longitude, temperature, condition_text, humidity, wind_speed, pressure, visibility, moon_phase, moon_illumination, moon_age, sunrise, sunset, best_observation_time, seeing_score, transparency_score, events_json, observed_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute(array($data['location'], isset($data['latitude']) ? $data['latitude'] : 35.6892, isset($data['longitude']) ? $data['longitude'] : 51.3890, $data['temperature'], $data['condition'], isset($data['humidity']) ? $data['humidity'] : 0, isset($data['wind']) ? $data['wind'] : 0, isset($data['pressure']) ? $data['pressure'] : 0, isset($data['visibility']) ? $data['visibility'] : 0, isset($data['moonPhase']) ? $data['moonPhase'] : '', isset($data['moonIllumination']) ? $data['moonIllumination'] : 0, isset($data['moonAge']) ? $data['moonAge'] : 0, isset($data['sunrise']) ? $data['sunrise'] : '', isset($data['sunset']) ? $data['sunset'] : '', isset($data['bestTime']) ? $data['bestTime'] : '', isset($data['seeing']) ? $data['seeing'] : 0, isset($data['transparency']) ? $data['transparency'] : 0, json_encode(isset($data['events']) ? $data['events'] : array(), JSON_UNESCAPED_UNICODE)));
        Response::success(array('id' => (int) Database::connection()->lastInsertId()), 'وضعیت آسمان به‌روزرسانی شد.', 201);
    }
}
