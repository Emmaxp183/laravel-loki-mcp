<?php

namespace LaravelMcpSuite\Tests\Feature\Resources;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Resources\ProjectConventionsResource;
use LaravelMcpSuite\Tests\TestCase;

class ProjectConventionsResourceTest extends TestCase
{
    public function test_it_uses_the_package_default_when_no_app_conventions_file_exists(): void
    {
        @unlink(base_path('docs/project-conventions.md'));

        $resource = $this->app->make(ProjectConventionsResource::class);
        $response = $resource->handle(new Request());
        $payload = $response->getStructuredContent();

        $this->assertStringContainsString('# Project Conventions', $payload['data']['markdown']);
        $this->assertTrue($payload['meta']['read_only']);
    }

    public function test_it_prefers_the_app_owned_conventions_file_when_present(): void
    {
        if (! is_dir(base_path('docs'))) {
            mkdir(base_path('docs'), 0777, true);
        }

        file_put_contents(base_path('docs/project-conventions.md'), "# Team Rules\n\nUse service classes.");

        $resource = $this->app->make(ProjectConventionsResource::class);
        $response = $resource->handle(new Request());
        $payload = $response->getStructuredContent();

        $this->assertStringContainsString('# Team Rules', $payload['data']['markdown']);
        $this->assertTrue($payload['meta']['read_only']);
    }
}
