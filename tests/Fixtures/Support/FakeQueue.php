<?php

namespace LaravelMcpSuite\Tests\Fixtures\Support;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue;

class FakeQueue implements Queue
{
    /**
     * @var array<int, array{payload: string, queue: string|null, options: array}>
     */
    public array $pushedRaw = [];

    public string $connectionName = 'fake';

    public function size($queue = null): int
    {
        return 0;
    }

    public function pendingSize($queue = null): int
    {
        return 0;
    }

    public function delayedSize($queue = null): int
    {
        return 0;
    }

    public function reservedSize($queue = null): int
    {
        return 0;
    }

    public function creationTimeOfOldestPendingJob($queue = null): ?int
    {
        return null;
    }

    public function push($job, $data = '', $queue = null): mixed
    {
        return null;
    }

    public function pushOn($queue, $job, $data = ''): mixed
    {
        return null;
    }

    public function pushRaw($payload, $queue = null, array $options = []): mixed
    {
        $this->pushedRaw[] = [
            'payload' => $payload,
            'queue' => $queue,
            'options' => $options,
        ];

        return 'raw-job-id';
    }

    public function later($delay, $job, $data = '', $queue = null): mixed
    {
        return null;
    }

    public function laterOn($queue, $delay, $job, $data = ''): mixed
    {
        return null;
    }

    public function bulk($jobs, $data = '', $queue = null): mixed
    {
        return [];
    }

    public function pop($queue = null): ?Job
    {
        return null;
    }

    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    public function setConnectionName($name): static
    {
        $this->connectionName = (string) $name;

        return $this;
    }
}
