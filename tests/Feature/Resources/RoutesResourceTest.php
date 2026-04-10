<?php

namespace LaravelMcpSuite\Tests\Feature\Resources;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Resources\RoutesResource;
use LaravelMcpSuite\Support\RouteInspector;
use LaravelMcpSuite\Tests\TestCase;

class RoutesResourceTest extends TestCase
{
    public function test_routes_resource_returns_route_context(): void
    {
        $resource = $this->app->make(RoutesResource::class);
        $response = $resource->handle(new Request(), $this->app->make(RouteInspector::class));
        $payload = $response->getStructuredContent();

        $this->assertArrayHasKey('by_method', $payload['data']);
        $this->assertArrayHasKey('by_controller', $payload['data']);
        $this->assertArrayHasKey('GET', $payload['data']['by_method']);
        $this->assertArrayHasKey('LaravelMcpSuite\Tests\Fixtures\TestRouteController', $payload['data']['by_controller']);
        $this->assertTrue($payload['meta']['read_only']);
    }
}
