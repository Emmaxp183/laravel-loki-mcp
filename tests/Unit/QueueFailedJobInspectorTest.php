<?php

namespace LaravelMcpSuite\Tests\Unit;

use LaravelMcpSuite\Support\QueueFailedJobInspector;
use LaravelMcpSuite\Tests\Fixtures\Support\FakeFailedJobProvider;
use PHPUnit\Framework\TestCase;

class QueueFailedJobInspectorTest extends TestCase
{
    public function test_it_normalizes_and_limits_failed_jobs(): void
    {
        $inspector = new QueueFailedJobInspector(new FakeFailedJobProvider([
            (object) [
                'id' => 7,
                'connection' => 'database',
                'queue' => 'emails',
                'failed_at' => '2026-04-14 10:00:00',
                'exception' => "RuntimeException: Job exploded\n#0 stack line",
            ],
            (object) [
                'id' => 8,
                'connection' => 'redis',
                'queue' => 'imports',
                'failed_at' => '2026-04-14 11:00:00',
                'exception' => 'Second failure',
            ],
        ]));

        $jobs = $inspector->list(1);

        $this->assertCount(1, $jobs);
        $this->assertSame(7, $jobs[0]['id']);
        $this->assertSame('database', $jobs[0]['connection']);
        $this->assertSame('emails', $jobs[0]['queue']);
        $this->assertSame('2026-04-14 10:00:00', $jobs[0]['failed_at']);
        $this->assertSame('RuntimeException: Job exploded', $jobs[0]['exception_summary']);
    }

    public function test_it_returns_an_empty_list_when_no_failed_jobs_exist(): void
    {
        $inspector = new QueueFailedJobInspector(new FakeFailedJobProvider());

        $this->assertSame([], $inspector->list());
    }
}
