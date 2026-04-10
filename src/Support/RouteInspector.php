<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

class RouteInspector
{
    public function __construct(
        protected Router $router,
    ) {
    }

    public function list(array $filters = []): array
    {
        return collect($this->router->getRoutes()->getRoutes())
            ->map(fn (Route $route): array => $this->normalizeRoute($route))
            ->filter(function (array $route) use ($filters): bool {
                if (($method = $filters['method'] ?? null) !== null && ! in_array(strtoupper($method), $route['methods'], true)) {
                    return false;
                }

                if (($middleware = $filters['middleware'] ?? null) !== null && ! collect($route['middleware'])->contains(fn (string $value): bool => str_contains($value, $middleware))) {
                    return false;
                }

                if (($pathContains = $filters['path_contains'] ?? null) !== null && ! str_contains($route['uri'], $pathContains)) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    public function grouped(): array
    {
        $routes = $this->list();
        $byMethod = [];

        foreach ($routes as $route) {
            foreach ($route['methods'] as $method) {
                $byMethod[$method][] = $this->resourceRoute($route);
            }
        }

        return [
            'by_method' => $byMethod,
            'by_controller' => collect($routes)
                ->groupBy('controller')
                ->map(fn ($group) => $group->map(fn (array $route): array => $this->resourceRoute($route))->values()->all())
                ->all(),
        ];
    }

    protected function normalizeRoute(Route $route): array
    {
        $action = $route->getActionName();
        $controller = str_contains($action, '@') ? explode('@', $action)[0] : $action;

        return [
            'uri' => '/'.ltrim($route->uri(), '/'),
            'methods' => array_values(array_filter($route->methods(), fn (string $method): bool => $method !== 'HEAD')),
            'name' => $route->getName(),
            'action' => $action,
            'controller' => $controller,
            'middleware' => $route->gatherMiddleware(),
        ];
    }

    protected function resourceRoute(array $route): array
    {
        return [
            'uri' => $route['uri'],
            'name' => $route['name'],
            'action' => $route['action'],
            'middleware' => $route['middleware'],
        ];
    }
}
