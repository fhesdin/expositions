<?php

namespace Core;

/**
 * Routeur simple avec paramètres nommés {id}, {slug}...
 */
final class Router
{
    private array $routes = [];
    private array $named = [];

    public function get(string $pattern, callable|array $handler, ?string $name = null): self
    {
        return $this->add('GET', $pattern, $handler, $name);
    }

    public function post(string $pattern, callable|array $handler, ?string $name = null): self
    {
        return $this->add('POST', $pattern, $handler, $name);
    }

    public function any(string $pattern, callable|array $handler, ?string $name = null): self
    {
        return $this->add('GET|POST', $pattern, $handler, $name);
    }

    private function add(string $methods, string $pattern, callable|array $handler, ?string $name): self
    {
        $regex = preg_replace('/\{([a-zA-Z_]\w*)\}/', '(?<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        $this->routes[] = [
            'methods' => explode('|', $methods),
            'pattern' => $pattern,
            'regex' => $regex,
            'handler' => $handler,
            'middleware' => [],
        ];
        if ($name !== null) {
            $this->named[$name] = $pattern;
        }
        return $this;
    }

    /** Ajoute un middleware (rôle minimum) à la dernière route ajoutée. */
    public function middleware(string $role): self
    {
        $this->routes[count($this->routes) - 1]['middleware'][] = $role;
        return $this;
    }

    private static array $roleRank = [
        'visiteur' => 1,
        'membre' => 2,
        'redacteur' => 3,
        'contributor' => 3,
        'moderator' => 4,
        'moderateur' => 4,
        'admin' => 5,
        'auth' => 2,
    ];

    private function checkMiddleware(array $middleware): void
    {
        foreach ($middleware as $role) {
            $required = self::$roleRank[$role] ?? 0;
            if (!\Core\Auth::minRank($required)) {
                if (!\Core\Auth::check()) {
                    \Core\Response::redirect('/login');
                }
                \Core\Response::forbidden('Rôle insuffisant pour cette action.');
            }
        }
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'], true)) {
                continue;
            }
            if (preg_match($route['regex'], $path, $m)) {
                $params = array_filter($m, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
                $this->checkMiddleware($route['middleware']);
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    $controller->$action($params);
                } else {
                    $handler($params);
                }
                return;
            }
        }
        Response::notFound();
    }

    public function named(string $name, array $params = []): string
    {
        $pattern = $this->named[$name] ?? throw new \RuntimeException("Route nommée inconnue : $name");
        foreach ($params as $key => $value) {
            $pattern = str_replace('{' . $key . '}', (string) $value, $pattern);
        }
        return $pattern;
    }
}
