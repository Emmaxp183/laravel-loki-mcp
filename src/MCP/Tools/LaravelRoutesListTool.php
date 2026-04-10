<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Support\RouteInspector;

#[Description('Return a filtered route inventory.')]
class LaravelRoutesListTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-routes-list';

    public function schema(JsonSchema $schema): array
    {
        return [
            'method' => $schema->string()->description('Optional HTTP method filter.'),
            'middleware' => $schema->string()->description('Optional middleware substring filter.'),
            'path_contains' => $schema->string()->description('Optional path substring filter.'),
        ];
    }

    public function handle(Request $request, RouteInspector $inspector): ResponseFactory
    {
        $validated = $request->validate([
            'method' => ['nullable', 'string'],
            'middleware' => ['nullable', 'string'],
            'path_contains' => ['nullable', 'string'],
        ]);

        $routes = $inspector->list($validated);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Laravel routes listed.',
            'data' => [
                'routes' => $routes,
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
