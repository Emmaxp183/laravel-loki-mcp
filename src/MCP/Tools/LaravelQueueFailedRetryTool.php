<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\QueueFailedJobOperator;

#[Description('Retry one failed Laravel queue job by id.')]
class LaravelQueueFailedRetryTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-queue-failed-retry';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required()->description('Failed queue job id to retry.'),
        ];
    }

    public function handle(Request $request, QueueFailedJobOperator $operator, EnvironmentPolicy $environmentPolicy): ResponseFactory
    {
        $validated = $request->validate([
            'id' => ['required'],
        ]);
        $environment = (string) config('app.env', app()->environment());

        if (! $environmentPolicy->queueMutationsEnabled($environment)) {
            return $this->auditedResponse($this->name(), $request, [
                'summary' => 'Failed queue job retry was denied.',
                'data' => [
                    'allowed' => false,
                    'id' => $validated['id'],
                    'retried' => false,
                    'deleted_from_failed' => false,
                    'connection' => null,
                    'queue' => null,
                ],
                'warnings' => ['Queue failed job mutations are disabled for the current environment or configuration.'],
                'meta' => [
                    'module' => 'queues',
                    'read_only' => false,
                    'environment' => $environment,
                ],
            ]);
        }

        $result = $operator->retry($validated['id']);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => $result['retried'] ? 'Failed queue job retried.' : 'Failed queue job was not found.',
            'data' => array_merge(['allowed' => true], $result),
            'warnings' => $result['retried'] ? [] : ['No failed queue job matched the given id.'],
            'meta' => [
                'module' => 'queues',
                'read_only' => false,
                'environment' => $environment,
            ],
        ]);
    }
}
