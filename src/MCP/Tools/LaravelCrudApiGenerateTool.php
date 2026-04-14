<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\CrudGenerator;

#[Description('Generate a conventional Laravel JSON CRUD API.')]
class LaravelCrudApiGenerateTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-crud-api-generate';

    public function schema(JsonSchema $schema): array
    {
        return [
            'resource' => $schema->string()->required()->description('Canonical resource name used to derive Laravel class names.'),
            'fields' => $schema->array()->required()->description('Structured field definitions for the generated CRUD resources.'),
        ];
    }

    public function handle(Request $request, CrudGenerator $generator, EnvironmentPolicy $environmentPolicy): ResponseFactory
    {
        $validated = $request->validate([
            'resource' => ['required', 'string'],
            'fields' => ['required', 'array', 'min:1'],
        ]);

        $environment = (string) config('app.env', app()->environment());

        if (! $environmentPolicy->codeEditsEnabled($environment)) {
            return $this->auditedResponse($this->name(), $request, [
                'summary' => 'CRUD API generation request was denied.',
                'data' => [
                    'allowed' => false,
                    'resource' => $validated['resource'],
                    'created' => [],
                    'updated' => [],
                    'skipped' => [],
                ],
                'warnings' => ['Code edits are disabled for the current environment or configuration.'],
                'meta' => [
                    'module' => 'generators',
                    'read_only' => false,
                    'environment' => $environment,
                ],
            ]);
        }

        $result = $generator->generate('api', $validated['resource'], $validated['fields']);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'CRUD API generated.',
            'data' => $result,
            'warnings' => [],
            'meta' => [
                'module' => 'generators',
                'read_only' => false,
                'environment' => $environment,
            ],
        ]);
    }
}
