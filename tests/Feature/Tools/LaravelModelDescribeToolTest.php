<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelModelDescribeTool;
use LaravelMcpSuite\Support\ModelInspector;
use LaravelMcpSuite\Tests\TestCase;

class LaravelModelDescribeToolTest extends TestCase
{
    public function test_it_describes_a_model_and_relationships(): void
    {
        $tool = $this->app->make(LaravelModelDescribeTool::class);
        $response = $tool->handle(new Request([
            'model' => 'LaravelMcpSuite\Tests\Fixtures\Models\Project',
        ]), $this->app->make(ModelInspector::class));
        $payload = $response->getStructuredContent();

        $this->assertSame('projects', $payload['data']['model']['table']);
        $this->assertContains('user', array_column($payload['data']['model']['relationships'], 'name'));
        $this->assertTrue($payload['meta']['read_only']);
    }
}
