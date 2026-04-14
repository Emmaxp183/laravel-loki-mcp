<?php

namespace LaravelMcpSuite\Tests\Unit;

use LaravelMcpSuite\Support\QueueFailedJobOperator;
use LaravelMcpSuite\Tests\Fixtures\Support\FakeFailedJobProvider;
use LaravelMcpSuite\Tests\Fixtures\Support\FakeQueue;
use LaravelMcpSuite\Tests\Fixtures\Support\FakeQueueFactory;
use PHPUnit\Framework\TestCase;

class QueueFailedJobOperatorTest extends TestCase
{
    public function test_it_retries_a_failed_job_by_requeueing_payload_and_forgetting_it(): void
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
        $factory = new FakeQueueFactory($queue);
        $operator = new QueueFailedJobOperator($provider, $factory);

        $result = $operator->retry(7);

        $this->assertTrue($result['retried']);
        $this->assertTrue($result['deleted_from_failed']);
        $this->assertSame('database', $result['connection']);
        $this->assertSame('emails', $result['queue']);
        $this->assertCount(1, $queue->pushedRaw);
        $this->assertSame('{"job":"SendMail"}', $queue->pushedRaw[0]['payload']);
        $this->assertSame(['database'], $factory->requestedConnections);
        $this->assertSame([], $provider->all());
    }

    public function test_it_reports_missing_failed_jobs_on_retry_without_requeueing(): void
    {
        $provider = new FakeFailedJobProvider();
        $queue = new FakeQueue();
        $operator = new QueueFailedJobOperator($provider, new FakeQueueFactory($queue));

        $result = $operator->retry(999);

        $this->assertFalse($result['retried']);
        $this->assertFalse($result['deleted_from_failed']);
        $this->assertCount(0, $queue->pushedRaw);
    }

    public function test_it_deletes_a_failed_job_by_id(): void
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

        $result = $operator->delete(8);

        $this->assertTrue($result['deleted']);
        $this->assertSame([], $provider->all());
    }
}
