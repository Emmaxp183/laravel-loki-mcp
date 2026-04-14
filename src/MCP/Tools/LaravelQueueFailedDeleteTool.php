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

#[Description('Delete one failed Laravel queue job by id.')]
class LaravelQueueFailedDeleteTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-queue-failed-delete';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required()->description('Failed queue job id to delete.'),
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
                'summary' => 'Failed queue job delete was denied.',
                'data' => [
                    'allowed' => false,
                    'id' => $validated['id'],
                    'deleted' => false,
                ],
                'warnings' => ['Queue failed job mutations are disabled for the current environment or configuration.'],
                'meta' => [
                    'module' => 'queues',
                    'read_only' => false,
                    'environment' => $environment,
                ],
            ]);
        }

        $result = $operator->delete($validated['id']);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => $result['deleted'] ? 'Failed queue job deleted.' : 'Failed queue job was not found.',
            'data' => array_merge(['allowed' => true], $result),
            'warnings' => $result['deleted'] ? [] : ['No failed queue job matched the given id.'],
            'meta' => [
                'module' => 'queues',
                'read_only' => false,
                'environment' => $environment,
            ],
        ]);
    }
}
