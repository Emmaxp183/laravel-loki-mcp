<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelTestsRunTool;
use LaravelMcpSuite\Tests\TestCase;

class LaravelTestsRunToolTest extends TestCase
{
    public function test_it_runs_a_constrained_test_target(): void
    {
        $tool = $this->app->make(LaravelTestsRunTool::class);
        $response = $tool->handle(new Request([
            'path' => 'tests/Feature/PackageBootTest.php',
        ]));
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['success']);
        $this->assertStringContainsString('PackageBootTest', $payload['data']['command']);
        $this->assertTrue($payload['meta']['read_only']);
    }
}
