<?php

namespace LaravelMcpSuite\Tests\Feature\Resources;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Resources\RecentErrorsResource;
use LaravelMcpSuite\Sanitizers\OutputSanitizer;
use LaravelMcpSuite\Support\ExceptionSummarizer;
use LaravelMcpSuite\Support\LogReader;
use LaravelMcpSuite\Tests\TestCase;

class RecentErrorsResourceTest extends TestCase
{
    public function test_recent_errors_resource_returns_exception_summaries(): void
    {
        $resource = $this->app->make(RecentErrorsResource::class);
        $response = $resource->handle(
            new Request(),
            $this->app->make(ExceptionSummarizer::class),
            $this->app->make(LogReader::class),
            $this->app->make(OutputSanitizer::class),
        );
        $payload = $response->getStructuredContent();

        $this->assertNotEmpty($payload['data']['exceptions']);
        $this->assertSame('RuntimeException', $payload['data']['exceptions'][0]['type']);
        $this->assertStringNotContainsString('secret-token', $payload['data']['exceptions'][0]['context']);
        $this->assertTrue($payload['meta']['read_only']);
        $this->assertSame('logs', $payload['meta']['module']);
    }
}
