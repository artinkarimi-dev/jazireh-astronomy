<?php

class NasaService
{
    public function fetchApod($date = null)
    {
        $key = Config::get('NASA_API_KEY', 'DEMO_KEY');
        $url = 'https://api.nasa.gov/planetary/apod?api_key=' . rawurlencode($key);
        if ($date) {
            $url .= '&date=' . rawurlencode($date);
        }
        return HttpClient::getJson($url);
    }

    public function syncApod($date = null)
    {
        $item = $this->fetchApod($date);
        $stmt = Database::connection()->prepare("INSERT INTO apod (apod_date, title, image_url, media_type, explanation, excerpt, credit, source_url, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE title = VALUES(title), image_url = VALUES(image_url), media_type = VALUES(media_type), explanation = VALUES(explanation), excerpt = VALUES(excerpt), credit = VALUES(credit), source_url = VALUES(source_url), updated_at = NOW()");
        $explanation = isset($item['explanation']) ? $item['explanation'] : '';
        $excerpt = function_exists('mb_substr') ? mb_substr($explanation, 0, 220) : substr($explanation, 0, 220);
        $stmt->execute(array(
            isset($item['date']) ? $item['date'] : date('Y-m-d'),
            isset($item['title']) ? $item['title'] : 'NASA APOD',
            isset($item['hdurl']) ? $item['hdurl'] : (isset($item['url']) ? $item['url'] : ''),
            isset($item['media_type']) ? $item['media_type'] : 'image',
            $explanation,
            $excerpt,
            isset($item['copyright']) ? $item['copyright'] : 'NASA',
            isset($item['url']) ? $item['url'] : ''
        ));
        return $item;
    }
}
