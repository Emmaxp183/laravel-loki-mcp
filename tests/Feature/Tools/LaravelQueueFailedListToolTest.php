<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelQueueFailedListTool;
use LaravelMcpSuite\Support\QueueFailedJobInspector;
use LaravelMcpSuite\Tests\Fixtures\Support\FakeFailedJobProvider;
use LaravelMcpSuite\Tests\TestCase;

class LaravelQueueFailedListToolTest extends TestCase
{
    public function test_it_lists_failed_jobs_in_a_normalized_shape(): void
    {
        $tool = $this->app->make(LaravelQueueFailedListTool::class);
        $inspector = new QueueFailedJobInspector(new FakeFailedJobProvider([
            (object) [
                'id' => 7,
                'connection' => 'database',
                'queue' => 'emails',
                'failed_at' => '2026-04-14 10:00:00',
                'exception' => "RuntimeException: Job exploded\n#0 stack line",
            ],
        ]));

        $response = $tool->handle(new Request([
            'limit' => 10,
        ]), $inspector);
        $payload = $response->getStructuredContent();

        $this->assertSame('Failed queue jobs loaded.', $payload['summary']);
        $this->assertCount(1, $payload['data']['jobs']);
        $this->assertSame('RuntimeException: Job exploded', $payload['data']['jobs'][0]['exception_summary']);
    }
}
