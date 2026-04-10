<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelExceptionLastTool;
use LaravelMcpSuite\Sanitizers\OutputSanitizer;
use LaravelMcpSuite\Support\ExceptionSummarizer;
use LaravelMcpSuite\Support\LogReader;
use LaravelMcpSuite\Tests\TestCase;

class LaravelExceptionLastToolTest extends TestCase
{
    public function test_it_returns_the_last_exception_summary(): void
    {
        $tool = $this->app->make(LaravelExceptionLastTool::class);
        $response = $tool->handle(
            new Request(),
            $this->app->make(ExceptionSummarizer::class),
            $this->app->make(LogReader::class),
            $this->app->make(OutputSanitizer::class),
        );
        $payload = $response->getStructuredContent();

        $this->assertSame('RuntimeException', $payload['data']['exception']['type']);
        $this->assertStringNotContainsString('secret-token', $payload['data']['exception']['context']);
        $this->assertTrue($payload['meta']['read_only']);
    }
}
