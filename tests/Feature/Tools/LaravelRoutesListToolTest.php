<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelRoutesListTool;
use LaravelMcpSuite\Support\RouteInspector;
use LaravelMcpSuite\Tests\TestCase;

class LaravelRoutesListToolTest extends TestCase
{
    public function test_route_tool_returns_named_routes(): void
    {
        $tool = $this->app->make(LaravelRoutesListTool::class);
        $response = $tool->handle(new Request([
            'method' => 'GET',
        ]), $this->app->make(RouteInspector::class));
        $payload = $response->getStructuredContent();

        $this->assertNotEmpty($payload['data']['routes']);
        $this->assertSame('health', $payload['data']['routes'][0]['name']);
        $this->assertTrue($payload['meta']['read_only']);
        $this->assertSame('core', $payload['meta']['module']);
    }
}
