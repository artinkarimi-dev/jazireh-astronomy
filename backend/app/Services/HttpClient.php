<?php

class HttpClient
{
    public static function getJson($url, $timeout = 15)
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('افزونه cURL در PHP فعال نیست.');
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => array('Accept: application/json', 'User-Agent: JazirehAstronomy/1.0')
        ));
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException($error ?: 'پاسخ سرویس خارجی معتبر نبود.');
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new RuntimeException('فرمت پاسخ سرویس خارجی معتبر نیست.');
        }
        return $data;
    }
}
