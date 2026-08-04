<?php

require_once __DIR__ . '/Config.php';
Config::load(dirname(__DIR__) . '/.env');

spl_autoload_register(function ($class) {
    $paths = array(
        __DIR__ . '/' . $class . '.php',
        __DIR__ . '/Controllers/' . $class . '.php',
        __DIR__ . '/Services/' . $class . '.php'
    );
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

date_default_timezone_set('Asia/Tehran');

$allowedOrigin = Config::get('FRONTEND_URL', 'http://localhost:5173');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : $allowedOrigin;
if ($origin === $allowedOrigin || Config::get('APP_ENV', 'local') === 'local') {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Vary: Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (Request::method() === 'OPTIONS') {
    http_response_code(204);
    exit;
}

set_exception_handler(function ($exception) {
    $debug = Config::get('APP_DEBUG', 'false') === 'true';
    $message = $debug ? $exception->getMessage() : 'خطای داخلی سرور رخ داد.';
    error_log($exception->getMessage() . PHP_EOL . $exception->getTraceAsString());
    Response::error($message, 500);
});
