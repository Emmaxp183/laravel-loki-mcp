<?php

namespace LaravelMcpSuite\Tests\Feature\Resources;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Resources\SchemaResource;
use LaravelMcpSuite\Support\SchemaInspector;
use LaravelMcpSuite\Tests\TestCase;

class SchemaResourceTest extends TestCase
{
    public function test_schema_resource_returns_read_only_schema_overview(): void
    {
        $resource = $this->app->make(SchemaResource::class);
        $response = $resource->handle(new Request(), $this->app->make(SchemaInspector::class));
        $payload = $response->getStructuredContent();

        $this->assertArrayHasKey('tables', $payload['data']);
        $this->assertContains('users', array_column($payload['data']['tables'], 'name'));
        $this->assertTrue($payload['meta']['read_only']);
        $this->assertSame('database', $payload['meta']['module']);
    }
}
