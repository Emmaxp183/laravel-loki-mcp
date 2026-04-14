<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Support\Str;

class QueueFailedJobInspector
{
    public function __construct(
        protected FailedJobProviderInterface $failedJobProvider,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(int $limit = 20): array
    {
        $limit = max(1, min($limit, 100));

        return array_map(function (object $job): array {
            return [
                'id' => $job->id ?? null,
                'connection' => $job->connection ?? null,
                'queue' => $job->queue ?? null,
                'failed_at' => $this->normalizeFailedAt($job->failed_at ?? null),
                'exception_summary' => $this->summarizeException($job->exception ?? null),
            ];
        }, array_slice($this->failedJobProvider->all(), 0, $limit));
    }

    protected function normalizeFailedAt(mixed $failedAt): ?string
    {
        if ($failedAt === null) {
            return null;
        }

        return trim((string) $failedAt) ?: null;
    }

    protected function summarizeException(mixed $exception): string
    {
        $text = trim((string) ($exception ?? ''));
        $firstLine = trim(Str::before($text, "\n"));

        return Str::limit($firstLine, 240, '...');
    }
}
