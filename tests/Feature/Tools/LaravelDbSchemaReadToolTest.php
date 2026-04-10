<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelDbSchemaReadTool;
use LaravelMcpSuite\Support\SchemaInspector;
use LaravelMcpSuite\Tests\TestCase;

class LaravelDbSchemaReadToolTest extends TestCase
{
    public function test_schema_tool_returns_table_overview_and_supports_table_filter(): void
    {
        $tool = $this->app->make(LaravelDbSchemaReadTool::class);
        $response = $tool->handle(new Request([
            'table' => 'projects',
        ]), $this->app->make(SchemaInspector::class));
        $payload = $response->getStructuredContent();

        $this->assertCount(1, $payload['data']['tables']);
        $this->assertSame('projects', $payload['data']['tables'][0]['name']);
        $this->assertNotEmpty($payload['data']['tables'][0]['columns']);
        $this->assertTrue($payload['meta']['read_only']);
        $this->assertSame('database', $payload['meta']['module']);
    }
}
