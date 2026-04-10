<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Support\SchemaInspector;

#[Description('Provide read-only database schema introspection.')]
class LaravelDbSchemaReadTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-db-schema-read';

    public function schema(JsonSchema $schema): array
    {
        return [
            'table' => $schema->string()->description('Optional table filter.'),
        ];
    }

    public function handle(Request $request, SchemaInspector $inspector): ResponseFactory
    {
        $validated = $request->validate([
            'table' => ['nullable', 'string'],
        ]);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Database schema loaded.',
            'data' => [
                'tables' => $inspector->overview($validated['table'] ?? null),
            ],
            'warnings' => [],
            'meta' => [
                'module' => 'database',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
