<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class QueueFailedJobOperator
{
    public function __construct(
        protected FailedJobProviderInterface $failedJobProvider,
        protected QueueFactory $queueFactory,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function retry(mixed $id): array
    {
        $job = $this->failedJobProvider->find($id);

        if ($job === null) {
            return [
                'id' => $id,
                'retried' => false,
                'deleted_from_failed' => false,
                'connection' => null,
                'queue' => null,
            ];
        }

        $this->queueFactory
            ->connection($job->connection ?? null)
            ->pushRaw((string) ($job->payload ?? ''), $job->queue ?? null);

        $deleted = $this->failedJobProvider->forget($id);

        return [
            'id' => $job->id ?? $id,
            'retried' => $deleted,
            'deleted_from_failed' => $deleted,
            'connection' => $job->connection ?? null,
            'queue' => $job->queue ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(mixed $id): array
    {
        return [
            'id' => $id,
            'deleted' => $this->failedJobProvider->forget($id),
        ];
    }
}
