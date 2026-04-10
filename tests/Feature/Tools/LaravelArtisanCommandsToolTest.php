<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelArtisanCommandsTool;
use LaravelMcpSuite\Support\ArtisanCommandPolicy;
use LaravelMcpSuite\Tests\TestCase;

class LaravelArtisanCommandsToolTest extends TestCase
{
    public function test_it_lists_commands_and_marks_allowlisted_entries(): void
    {
        $tool = $this->app->make(LaravelArtisanCommandsTool::class);
        $response = $tool->handle(new Request([
            'search' => 'about',
        ]), $this->app->make(ArtisanCommandPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertNotEmpty($payload['data']['commands']);
        $this->assertSame('about', $payload['data']['commands'][0]['name']);
        $this->assertTrue($payload['data']['commands'][0]['allowed']);
        $this->assertTrue($payload['meta']['read_only']);
        $this->assertSame('artisan', $payload['meta']['module']);
    }
}
