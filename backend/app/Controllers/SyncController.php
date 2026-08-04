<?php

class SyncController
{
    public function apod()
    {
        $data = Request::json();
        $service = new NasaService();
        $item = $service->syncApod(isset($data['date']) ? $data['date'] : null);
        Response::success($item, 'تصویر روز ناسا همگام‌سازی شد.');
    }

    public function weather()
    {
        $data = Request::json();
        $latitude = isset($data['latitude']) ? (float) $data['latitude'] : 35.6892;
        $longitude = isset($data['longitude']) ? (float) $data['longitude'] : 51.3890;
        $location = isset($data['location']) ? Validator::clean($data['location']) : 'تهران، ایران';
        $service = new WeatherService();
        $item = $service->syncCurrent($latitude, $longitude, $location);
        Response::success($item, 'وضعیت هوا همگام‌سازی شد.');
    }
}
