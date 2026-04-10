<?php

namespace LaravelMcpSuite\Tests\Feature\Audit;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelAppInfoTool;
use LaravelMcpSuite\MCP\Tools\LaravelArtisanRunSafeTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\ArtisanCommandPolicy;
use LaravelMcpSuite\Support\AuditLogger;
use LaravelMcpSuite\Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    public function test_it_records_allowed_and_denied_tool_calls(): void
    {
        $logger = $this->app->make(AuditLogger::class);

        $this->app->make(LaravelAppInfoTool::class)->handle(new Request());

        config()->set('app.env', 'staging');
        $this->app->make(LaravelArtisanRunSafeTool::class)->handle(
            new Request([
                'command' => 'about',
                'arguments' => [],
            ]),
            $this->app->make(ArtisanCommandPolicy::class),
            $this->app->make(EnvironmentPolicy::class),
        );

        $entries = $logger->all();

        $this->assertCount(2, $entries);
        $this->assertSame('laravel-app-info', $entries[0]['tool']);
        $this->assertSame('allowed', $entries[0]['result']);
        $this->assertSame('laravel-artisan-run-safe', $entries[1]['tool']);
        $this->assertSame('denied', $entries[1]['result']);
        $this->assertArrayHasKey('timestamp', $entries[0]);
        $this->assertArrayHasKey('arguments', $entries[1]);
    }
}
