<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelAppInfoTool;
use LaravelMcpSuite\Tests\TestCase;

class LaravelAppInfoToolTest extends TestCase
{
    public function test_it_returns_framework_and_environment_metadata(): void
    {
        $tool = $this->app->make(LaravelAppInfoTool::class);
        $response = $tool->handle($this->app->make(Request::class));
        $payload = $response->getStructuredContent();

        $this->assertSame($this->app->environment(), $payload['data']['app']['environment']);
        $this->assertIsBool($payload['data']['app']['debug']);
        $this->assertArrayHasKey('laravel_version', $payload['data']['framework']);
        $this->assertArrayHasKey('php_version', $payload['data']['framework']);
        $this->assertArrayHasKey('horizon', $payload['data']['integrations']);
        $this->assertArrayHasKey('telescope', $payload['data']['integrations']);
        $this->assertSame('core', $payload['meta']['module']);
        $this->assertTrue($payload['meta']['read_only']);
    }
}
