<?php

namespace LaravelMcpSuite\Tests\Fixtures\Support;

use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Throwable;

class FakeFailedJobProvider implements FailedJobProviderInterface
{
    /**
     * @param  array<int, object>  $jobs
     */
    public function __construct(
        public array $jobs = [],
    ) {
    }

    public function log($connection, $queue, $payload, $exception)
    {
        $job = (object) [
            'id' => count($this->jobs) + 1,
            'connection' => $connection,
            'queue' => $queue,
            'payload' => $payload,
            'exception' => $exception instanceof Throwable ? $exception->getMessage() : '',
            'failed_at' => now()->toDateTimeString(),
        ];

        $this->jobs[] = $job;

        return $job->id;
    }

    public function ids($queue = null): array
    {
        return array_values(array_map(
            static fn (object $job): mixed => $job->id ?? null,
            array_filter($this->jobs, static function (object $job) use ($queue): bool {
                if ($queue === null) {
                    return true;
                }

                return ($job->queue ?? null) === $queue;
            }),
        ));
    }

    public function all(): array
    {
        return array_values($this->jobs);
    }

    public function find($id): ?object
    {
        foreach ($this->jobs as $job) {
            if ((string) ($job->id ?? '') === (string) $id) {
                return $job;
            }
        }

        return null;
    }

    public function forget($id): bool
    {
        foreach ($this->jobs as $index => $job) {
            if ((string) ($job->id ?? '') === (string) $id) {
                unset($this->jobs[$index]);
                $this->jobs = array_values($this->jobs);

                return true;
            }
        }

        return false;
    }

    public function flush($hours = null): void
    {
        $this->jobs = [];
    }
}
