<?php

class Validator
{
    public static function required($data, $fields)
    {
        $errors = array();
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $errors[$field] = 'این فیلد الزامی است.';
            }
        }
        return $errors;
    }

    public static function email($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function slug($value)
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) === 1;
    }

    public static function clean($value)
    {
        return trim(strip_tags((string) $value));
    }
}
