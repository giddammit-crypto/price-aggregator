<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private mixed $notFoundHandler = null;

    public function get(string $pattern, callable|array $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function addRoute(string $method, string $pattern, callable|array $handler): void
    {
        $pattern = rtrim($pattern, '/') ?: '/';
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $this->compilePattern($pattern),
            'handler' => $handler
        ];
    }

    public function setNotFoundHandler(callable|array $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $path = $request->getPath();
        $method = $request->getMethod();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && $route['method'] !== 'ANY') {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        $params[$k] = $v;
                    }
                }

                return $this->invokeHandler($route['handler'], $request, $params);
            }
        }

        if ($this->notFoundHandler !== null) {
            return $this->invokeHandler($this->notFoundHandler, $request, []);
        }

        return Response::html('<h1>404 Not Found</h1>', 404);
    }

    private function compilePattern(string $pattern): string
    {
        // Replace {param:[^/]+} or {param}
        $regex = preg_replace_callback('/\{([a-zA-Z0-9_]+)(?::([^}]+))?\}/', function ($m) {
            $name = $m[1];
            $rule = $m[2] ?? '[^/]+';
            return "(?P<{$name}>{$rule})";
        }, $pattern);

        return '#^' . $regex . '$#u';
    }

    private function invokeHandler(mixed $handler, Request $request, array $params): Response
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = is_string($class) ? new $class() : $class;
            $res = $instance->$method($request, $params);
        } elseif (is_callable($handler)) {
            $res = $handler($request, $params);
        } else {
            throw new \RuntimeException("Invalid route handler.");
        }

        if ($res instanceof Response) {
            return $res;
        }
        if (is_string($res)) {
            return Response::html($res);
        }
        if (is_array($res)) {
            return Response::json($res);
        }

        return new Response();
    }
}
