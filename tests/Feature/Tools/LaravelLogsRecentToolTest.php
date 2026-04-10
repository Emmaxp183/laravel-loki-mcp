<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelLogsRecentTool;
use LaravelMcpSuite\Sanitizers\OutputSanitizer;
use LaravelMcpSuite\Support\LogReader;
use LaravelMcpSuite\Tests\TestCase;

class LaravelLogsRecentToolTest extends TestCase
{
    public function test_log_tool_enforces_filters_and_sanitizes_output(): void
    {
        $tool = $this->app->make(LaravelLogsRecentTool::class);
        $response = $tool->handle(new Request([
            'lines' => 2,
            'level' => 'error',
        ]), $this->app->make(LogReader::class), $this->app->make(OutputSanitizer::class));
        $payload = $response->getStructuredContent();

        $this->assertCount(1, $payload['data']['entries']);
        $this->assertStringNotContainsString('secret-token', $payload['data']['entries'][0]['line']);
        $this->assertStringNotContainsString('hidden-value', $payload['data']['entries'][0]['line']);
        $this->assertTrue($payload['meta']['read_only']);
        $this->assertSame('logs', $payload['meta']['module']);
    }
}
