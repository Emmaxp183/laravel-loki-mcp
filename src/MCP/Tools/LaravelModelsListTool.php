<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Support\ModelInspector;

#[Description('List discovered Eloquent models.')]
class LaravelModelsListTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-models-list';

    public function schema(JsonSchema $schema): array
    {
        return [
            'namespace_prefix' => $schema->string()->description('Optional namespace prefix filter.'),
        ];
    }

    public function handle(Request $request, ModelInspector $inspector): ResponseFactory
    {
        $validated = $request->validate([
            'namespace_prefix' => ['nullable', 'string'],
        ]);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Laravel models listed.',
            'data' => [
                'models' => $inspector->list($validated['namespace_prefix'] ?? null),
            ],
            'warnings' => [],
            'meta' => [
                'module' => 'core',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
