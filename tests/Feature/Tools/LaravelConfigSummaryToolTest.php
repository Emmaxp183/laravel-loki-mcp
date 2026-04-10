<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelConfigSummaryTool;
use LaravelMcpSuite\Tests\TestCase;

class LaravelConfigSummaryToolTest extends TestCase
{
    public function test_it_returns_safe_config_summary_without_secrets(): void
    {
        config()->set('database.connections.testing.password', 'top-secret');

        $tool = $this->app->make(LaravelConfigSummaryTool::class);
        $response = $tool->handle(new Request());
        $payload = $response->getStructuredContent();

        $this->assertSame('sqlite', $payload['data']['database']['default_driver']);
        $this->assertArrayNotHasKey('password', $payload['data']['database']);
        $this->assertTrue($payload['meta']['read_only']);
    }
}
