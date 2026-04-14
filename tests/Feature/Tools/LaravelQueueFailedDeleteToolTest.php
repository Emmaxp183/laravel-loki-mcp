<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelQueueFailedDeleteTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\QueueFailedJobOperator;
use LaravelMcpSuite\Tests\Fixtures\Support\FakeFailedJobProvider;
use LaravelMcpSuite\Tests\Fixtures\Support\FakeQueue;
use LaravelMcpSuite\Tests\Fixtures\Support\FakeQueueFactory;
use LaravelMcpSuite\Tests\TestCase;

class LaravelQueueFailedDeleteToolTest extends TestCase
{
    public function test_it_deletes_one_failed_job_when_mutations_are_enabled(): void
    {
        $provider = new FakeFailedJobProvider([
            (object) [
                'id' => 8,
                'connection' => 'redis',
                'queue' => 'imports',
                'payload' => '{"job":"Import"}',
            ],
        ]);
        $operator = new QueueFailedJobOperator($provider, new FakeQueueFactory(new FakeQueue()));
        $tool = $this->app->make(LaravelQueueFailedDeleteTool::class);

        $response = $tool->handle(
            new Request(['id' => 8]),
            $operator,
            $this->app->make(EnvironmentPolicy::class),
        );
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertTrue($payload['data']['deleted']);
        $this->assertSame([], $provider->all());
    }

    public function test_it_denies_deletes_when_queue_mutations_are_disabled(): void
    {
        config()->set('laravel-mcp.queue_tools.allow_mutations_in_local', false);

        $provider = new FakeFailedJobProvider([
            (object) [
                'id' => 8,
                'connection' => 'redis',
                'queue' => 'imports',
                'payload' => '{"job":"Import"}',
            ],
        ]);
        $operator = new QueueFailedJobOperator($provider, new FakeQueueFactory(new FakeQueue()));
        $tool = $this->app->make(LaravelQueueFailedDeleteTool::class);

        $response = $tool->handle(
            new Request(['id' => 8]),
            $operator,
            $this->app->make(EnvironmentPolicy::class),
        );
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        $this->assertFalse($payload['data']['deleted']);
        $this->assertCount(1, $provider->all());
    }

    public function test_it_reports_missing_failed_jobs_when_delete_target_is_unknown(): void
    {
        $operator = new QueueFailedJobOperator(new FakeFailedJobProvider(), new FakeQueueFactory(new FakeQueue()));
        $tool = $this->app->make(LaravelQueueFailedDeleteTool::class);

        $response = $tool->handle(
            new Request(['id' => 999]),
            $operator,
            $this->app->make(EnvironmentPolicy::class),
        );
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertFalse($payload['data']['deleted']);
    }
}
