<?php

declare(strict_types=1);

namespace MailForge\Core;

use Closure;
use RuntimeException;

class Router
{
    /** @var array<int, array{method: string, path: string, handler: array|Closure, name: string|null}> */
    private array $routes = [];

    /** @var array<string, string> */
    private array $namedRoutes = [];

    private string $groupPrefix = '';

    private Closure|null $notFoundHandler = null;

    // ─── Route registration ───────────────────────────────────────────────

    public function get(string $path, array|Closure $handler, ?string $name = null): static
    {
        return $this->add('GET', $path, $handler, $name);
    }

    public function post(string $path, array|Closure $handler, ?string $name = null): static
    {
        return $this->add('POST', $path, $handler, $name);
    }

    public function put(string $path, array|Closure $handler, ?string $name = null): static
    {
        return $this->add('PUT', $path, $handler, $name);
    }

    public function patch(string $path, array|Closure $handler, ?string $name = null): static
    {
        return $this->add('PATCH', $path, $handler, $name);
    }

    public function delete(string $path, array|Closure $handler, ?string $name = null): static
    {
        return $this->add('DELETE', $path, $handler, $name);
    }

    public function add(string $method, string $path, array|Closure $handler, ?string $name = null): static
    {
        $fullPath = $this->groupPrefix . '/' . ltrim($path, '/');
        $fullPath = '/' . ltrim($fullPath, '/');
        $fullPath = rtrim($fullPath, '/') ?: '/';

        $route = [
            'method'  => strtoupper($method),
            'path'    => $fullPath,
            'handler' => $handler,
            'name'    => $name,
        ];

        $this->routes[] = $route;

        if ($name !== null) {
            $this->namedRoutes[$name] = $fullPath;
        }

        return $this;
    }

    // ─── Route grouping ───────────────────────────────────────────────────

    public function group(string $prefix, Closure $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $this->groupPrefix = $previousPrefix . '/' . trim($prefix, '/');

        $callback($this);

        $this->groupPrefix = $previousPrefix;
    }

    // ─── 404 handler ─────────────────────────────────────────────────────

    public function setNotFound(Closure $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    // ─── Dispatch ─────────────────────────────────────────────────────────

    public function dispatch(Request $request): void
    {
        $method = $request->method;
        $uri    = $this->normaliseUri($request->uri);

        // Strip base path for subdirectory installations (e.g. /mail/login → /login)
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath)) ?: '/';
            $uri = '/' . ltrim($uri, '/');
        }

        // Support method override via hidden _method field or X-HTTP-Method-Override header
        if ($method === 'POST') {
            $override = $request->body['_method'] ?? $request->headers['X-Http-Method-Override'] ?? null;
            if ($override !== null && in_array(strtoupper($override), ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = strtoupper($override);
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchPath($route['path'], $uri);

            if ($params === null) {
                continue;
            }

            $request->params = $params;
            $this->callHandler($route['handler'], $request);
            return;
        }

        $this->handleNotFound($request);
    }

    // ─── Named route URL generation ───────────────────────────────────────

    public function urlFor(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new RuntimeException("Named route '{$name}' not found.");
        }

        $path = $this->namedRoutes[$name];

        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', (string) $value, $path);
        }

        return $path;
    }

    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /**
     * Attempt to match a route pattern against a URI.
     * Returns an array of captured parameters on success, or null on no match.
     *
     * @return array<string, string>|null
     */
    private function matchPath(string $routePath, string $uri): ?array
    {
        // Convert route placeholders to named regex captures
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#u';

        if (preg_match($pattern, $uri, $matches) !== 1) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    private function normaliseUri(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/') ?: '/';

        return $path;
    }

    private function callHandler(array|Closure $handler, Request $request): void
    {
        if ($handler instanceof Closure) {
            $handler($request);
            return;
        }

        [$controllerClass, $action] = $handler;

        if (!class_exists($controllerClass)) {
            throw new RuntimeException("Controller class '{$controllerClass}' not found.");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $action)) {
            throw new RuntimeException(
                "Action '{$action}' not found on controller '{$controllerClass}'."
            );
        }

        $controller->$action($request);
    }

    private function handleNotFound(Request $request): void
    {
        if ($this->notFoundHandler !== null) {
            ($this->notFoundHandler)($request);
            return;
        }

        http_response_code(404);

        if (str_contains($request->headers['Accept'] ?? '', 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not Found', 'status' => 404]);
        } else {
            echo '<h1>404 Not Found</h1><p>The requested page could not be found.</p>';
        }
    }
}
