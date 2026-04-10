<?php

namespace LaravelMcpSuite\Tests\Feature\Routes;

use LaravelMcpSuite\Http\Middleware\EnsureSharedMcpToken;
use LaravelMcpSuite\Support\AiRouteRegistrar;
use LaravelMcpSuite\Tests\TestCase;

class AiRouteRegistrarTest extends TestCase
{
    public function test_it_only_registers_the_local_server_by_default(): void
    {
        app(AiRouteRegistrar::class)->register();

        $route = $this->findRoute('mcp/app', 'POST');

        $this->assertNull($route);
    }

    public function test_it_registers_a_web_server_with_shared_token_auth_when_enabled(): void
    {
        config()->set('laravel-mcp.server.enable_web_server', true);
        config()->set('laravel-mcp.server.web_middleware', ['web', 'throttle:api']);
        config()->set('laravel-mcp.server.auth.mode', 'shared_token');

        app(AiRouteRegistrar::class)->register();

        $route = $this->findRoute('mcp/app', 'POST');

        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('throttle:api', $route->gatherMiddleware());
        $this->assertContains(EnsureSharedMcpToken::class, $route->gatherMiddleware());
    }

    public function test_it_skips_oauth_metadata_routes_when_passport_is_unavailable(): void
    {
        config()->set('laravel-mcp.server.enable_web_server', true);
        config()->set('laravel-mcp.server.auth.mode', 'passport_oauth');

        app(AiRouteRegistrar::class)->register();

        $oauthRoute = $this->app['router']->getRoutes()->getByName('mcp.oauth.protected-resource');

        $this->assertNull($oauthRoute);
    }

    protected function findRoute(string $uri, string $method): ?object
    {
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                return $route;
            }
        }

        return null;
    }
}
