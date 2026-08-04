<?php

class Response
{
    public static function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success($data = null, $message = 'عملیات با موفقیت انجام شد', $status = 200)
    {
        self::json(array('success' => true, 'message' => $message, 'data' => $data), $status);
    }

    public static function error($message, $status = 400, $errors = array())
    {
        self::json(array('success' => false, 'message' => $message, 'errors' => $errors), $status);
    }
}
