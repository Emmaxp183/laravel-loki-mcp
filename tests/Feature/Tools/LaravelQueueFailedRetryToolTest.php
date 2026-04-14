<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelQueueFailedRetryTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\QueueFailedJobOperator;
use LaravelMcpSuite\Tests\Fixtures\Support\FakeFailedJobProvider;
use LaravelMcpSuite\Tests\Fixtures\Support\FakeQueue;
use LaravelMcpSuite\Tests\Fixtures\Support\FakeQueueFactory;
use LaravelMcpSuite\Tests\TestCase;

class LaravelQueueFailedRetryToolTest extends TestCase
{
    public function test_it_retries_one_failed_job_when_mutations_are_enabled(): void
    {
        $provider = new FakeFailedJobProvider([
            (object) [
                'id' => 7,
                'connection' => 'database',
                'queue' => 'emails',
                'payload' => '{"job":"SendMail"}',
            ],
        ]);
        $queue = new FakeQueue();
        $operator = new QueueFailedJobOperator($provider, new FakeQueueFactory($queue));
        $tool = $this->app->make(LaravelQueueFailedRetryTool::class);

        $response = $tool->handle(
            new Request(['id' => 7]),
            $operator,
            $this->app->make(EnvironmentPolicy::class),
        );
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertTrue($payload['data']['retried']);
        $this->assertCount(1, $queue->pushedRaw);
    }

    public function test_it_denies_retries_when_queue_mutations_are_disabled(): void
    {
        config()->set('laravel-mcp.queue_tools.allow_mutations_in_local', false);

        $provider = new FakeFailedJobProvider([
            (object) [
                'id' => 7,
                'connection' => 'database',
                'queue' => 'emails',
                'payload' => '{"job":"SendMail"}',
            ],
        ]);
        $queue = new FakeQueue();
        $operator = new QueueFailedJobOperator($provider, new FakeQueueFactory($queue));
        $tool = $this->app->make(LaravelQueueFailedRetryTool::class);

        $response = $tool->handle(
            new Request(['id' => 7]),
            $operator,
            $this->app->make(EnvironmentPolicy::class),
        );
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        $this->assertFalse($payload['data']['retried']);
        $this->assertCount(0, $queue->pushedRaw);
    }

    public function test_it_reports_missing_failed_jobs_when_retry_target_is_unknown(): void
    {
        $operator = new QueueFailedJobOperator(new FakeFailedJobProvider(), new FakeQueueFactory(new FakeQueue()));
        $tool = $this->app->make(LaravelQueueFailedRetryTool::class);

        $response = $tool->handle(
            new Request(['id' => 999]),
            $operator,
            $this->app->make(EnvironmentPolicy::class),
        );
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertFalse($payload['data']['retried']);
    }
}
