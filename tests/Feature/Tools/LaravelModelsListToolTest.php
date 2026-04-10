<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelModelsListTool;
use LaravelMcpSuite\Support\ModelInspector;
use LaravelMcpSuite\Tests\TestCase;

class LaravelModelsListToolTest extends TestCase
{
    public function test_it_lists_discovered_models(): void
    {
        $tool = $this->app->make(LaravelModelsListTool::class);
        $response = $tool->handle(new Request(), $this->app->make(ModelInspector::class));
        $payload = $response->getStructuredContent();

        $this->assertContains('LaravelMcpSuite\Tests\Fixtures\Models\Project', array_column($payload['data']['models'], 'class'));
        $this->assertTrue($payload['meta']['read_only']);
    }
}
