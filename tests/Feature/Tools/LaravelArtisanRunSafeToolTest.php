<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelArtisanRunSafeTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\ArtisanCommandPolicy;
use LaravelMcpSuite\Tests\TestCase;

class LaravelArtisanRunSafeToolTest extends TestCase
{
    public function test_it_runs_allowlisted_commands_in_local(): void
    {
        config()->set('app.env', 'local');

        $tool = $this->app->make(LaravelArtisanRunSafeTool::class);
        $response = $tool->handle(new Request([
            'command' => 'about',
            'arguments' => [],
        ]), $this->app->make(ArtisanCommandPolicy::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertSame('about', $payload['data']['command']);
        $this->assertTrue($payload['data']['allowed']);
        $this->assertTrue($payload['data']['success']);
        $this->assertSame('artisan', $payload['meta']['module']);
    }

    public function test_it_rejects_non_allowlisted_commands(): void
    {
        config()->set('app.env', 'local');

        $tool = $this->app->make(LaravelArtisanRunSafeTool::class);
        $response = $tool->handle(new Request([
            'command' => 'migrate',
            'arguments' => [],
        ]), $this->app->make(ArtisanCommandPolicy::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        $this->assertFalse($payload['data']['success']);
    }

    public function test_it_denies_write_capable_execution_outside_local(): void
    {
        config()->set('app.env', 'staging');

        $tool = $this->app->make(LaravelArtisanRunSafeTool::class);
        $response = $tool->handle(new Request([
            'command' => 'about',
            'arguments' => [],
        ]), $this->app->make(ArtisanCommandPolicy::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['success']);
        $this->assertSame('staging', $payload['meta']['environment']);
    }
}
