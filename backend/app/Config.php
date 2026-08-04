<?php

class Config
{
    private static $values = array();

    public static function load($path)
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || substr($trimmed, 0, 1) === '#') {
                continue;
            }

            $parts = explode('=', $trimmed, 2);
            $key = trim($parts[0]);
            $value = isset($parts[1]) ? trim($parts[1]) : '';
            $value = trim($value, "\"'");
            self::$values[$key] = $value;
            putenv($key . '=' . $value);
        }
    }

    public static function get($key, $default = null)
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}
