<?php

class WeatherService
{
    public function fetchCurrent($latitude, $longitude)
    {
        $key = Config::get('OPENWEATHER_API_KEY', '');
        if ($key === '') {
            throw new RuntimeException('کلید OpenWeather در فایل .env تنظیم نشده است.');
        }
        $url = 'https://api.openweathermap.org/data/2.5/weather?lat=' . rawurlencode($latitude) . '&lon=' . rawurlencode($longitude) . '&appid=' . rawurlencode($key) . '&units=metric&lang=fa';
        return HttpClient::getJson($url);
    }

    public function syncCurrent($latitude, $longitude, $location)
    {
        $data = $this->fetchCurrent($latitude, $longitude);
        $main = isset($data['main']) ? $data['main'] : array();
        $wind = isset($data['wind']) ? $data['wind'] : array();
        $weather = isset($data['weather'][0]) ? $data['weather'][0] : array();
        $visibility = isset($data['visibility']) ? ((float) $data['visibility'] / 1000) : 0;
        $sunrise = isset($data['sys']['sunrise']) ? date('H:i', (int) $data['sys']['sunrise']) : '';
        $sunset = isset($data['sys']['sunset']) ? date('H:i', (int) $data['sys']['sunset']) : '';
        $stmt = Database::connection()->prepare('INSERT INTO sky_conditions (location_name, latitude, longitude, temperature, condition_text, humidity, wind_speed, pressure, visibility, moon_phase, moon_illumination, moon_age, sunrise, sunset, best_observation_time, seeing_score, transparency_score, events_json, observed_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute(array(
            $location,
            $latitude,
            $longitude,
            isset($main['temp']) ? $main['temp'] : 0,
            isset($weather['description']) ? $weather['description'] : 'نامشخص',
            isset($main['humidity']) ? $main['humidity'] : 0,
            isset($wind['speed']) ? round($wind['speed'] * 3.6, 1) : 0,
            isset($main['pressure']) ? $main['pressure'] : 0,
            $visibility,
            '', 0, 0,
            $sunrise,
            $sunset,
            '', 0, 0,
            '[]'
        ));
        return $data;
    }
}
