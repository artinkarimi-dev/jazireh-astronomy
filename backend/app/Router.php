<?php

class Router
{
    private $routes = array();

    public function add($method, $path, $handler, $auth = false)
    {
        $this->routes[] = array(
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'auth' => $auth
        );
    }

    public function get($path, $handler, $auth = false)
    {
        $this->add('GET', $path, $handler, $auth);
    }

    public function post($path, $handler, $auth = false)
    {
        $this->add('POST', $path, $handler, $auth);
    }

    public function put($path, $handler, $auth = false)
    {
        $this->add('PUT', $path, $handler, $auth);
    }

    public function delete($path, $handler, $auth = false)
    {
        $this->add('DELETE', $path, $handler, $auth);
    }

    public function dispatch()
    {
        $method = Request::method();
        $path = Request::path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . rtrim($pattern, '/') . '/?$#';

            if (preg_match($pattern, $path, $matches)) {
                $params = array();
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $params[$key] = urldecode($value);
                    }
                }

                if ($route['auth']) {
                    Auth::requireAdmin();
                }

                call_user_func($route['handler'], $params);
                return;
            }
        }

        Response::error('مسیر API پیدا نشد.', 404);
    }
}
