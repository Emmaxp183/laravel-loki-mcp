<?php

namespace LaravelMcpSuite\Tests\Feature\Resources;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Resources\ModelsResource;
use LaravelMcpSuite\Support\ModelInspector;
use LaravelMcpSuite\Tests\TestCase;

class ModelsResourceTest extends TestCase
{
    public function test_models_resource_returns_model_context(): void
    {
        $resource = $this->app->make(ModelsResource::class);
        $response = $resource->handle(new Request(), $this->app->make(ModelInspector::class));
        $payload = $response->getStructuredContent();

        $this->assertContains('LaravelMcpSuite\Tests\Fixtures\Models\User', array_column($payload['data']['models'], 'class'));
        $this->assertTrue($payload['meta']['read_only']);
    }
}
