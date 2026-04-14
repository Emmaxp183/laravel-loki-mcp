<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Support\QueueFailedJobInspector;

#[Description('List failed Laravel queue jobs in a normalized shape.')]
class LaravelQueueFailedListTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-queue-failed-list';

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->min(1)->max(100)->description('Maximum number of failed jobs to return.'),
        ];
    }

    public function handle(Request $request, QueueFailedJobInspector $inspector): ResponseFactory
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $jobs = $inspector->list((int) ($validated['limit'] ?? 20));

        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Failed queue jobs loaded.',
            'data' => [
                'jobs' => $jobs,
                'count' => count($jobs),
            ],
            'warnings' => [],
            'meta' => [
                'module' => 'queues',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
