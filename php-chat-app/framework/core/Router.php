<?php
declare(strict_types=1);

namespace Framework\Core;

use RuntimeException;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, string $action, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $action, $middleware);
    }

    public function post(string $path, string $action, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $action, $middleware);
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();
        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            http_response_code(404);
            echo 'Page not found.';
            return;
        }

        $this->runMiddleware($route['middleware'] ?? [], $request);

        $action = $route['action'];
        [$controllerName, $controllerMethod] = explode('@', $action);
        $controllerClass = 'App\\Controllers\\' . $controllerName;

        if (!class_exists($controllerClass) || !method_exists($controllerClass, $controllerMethod)) {
            throw new RuntimeException('Route target is not valid.');
        }

        $controller = new $controllerClass();
        $controller->{$controllerMethod}($request);
    }

    private function addRoute(string $method, string $path, string $action, array $middleware = []): void
    {
        $normalizedPath = rtrim($path, '/') ?: '/';
        $this->routes[$method][$normalizedPath] = [
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    private function runMiddleware(array $middleware, Request $request): void
    {
        foreach ($middleware as $middlewareName) {
            $middlewareClass = 'Framework\\Middleware\\' . ucfirst($middlewareName) . 'Middleware';

            if (!class_exists($middlewareClass)) {
                throw new RuntimeException('Middleware is not valid.');
            }

            $instance = new $middlewareClass();
            $instance->handle($request);
        }
    }
}
