<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\DatabaseMutationPolicy;
use LaravelMcpSuite\Support\DatabaseMutator;

#[Description('Create one record on an allowlisted database table.')]
class LaravelDbRecordCreateTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-db-record-create';

    public function schema(JsonSchema $schema): array
    {
        return [
            'table' => $schema->string()->required()->description('Allowlisted database table name.'),
            'record' => $schema->object()->required()->description('Associative object of column values to insert.'),
        ];
    }

    public function handle(Request $request, DatabaseMutator $mutator, DatabaseMutationPolicy $mutationPolicy, EnvironmentPolicy $environmentPolicy): ResponseFactory
    {
        $validated = $request->validate([
            'table' => ['required', 'string'],
            'record' => ['required', 'array'],
        ]);

        $environment = (string) config('app.env', app()->environment());

        if (! $environmentPolicy->databaseMutationsEnabled($environment)) {
            return $this->auditedResponse($this->name(), $request, [
                'summary' => 'Database create request was denied.',
                'data' => [
                    'allowed' => false,
                    'table' => $validated['table'],
                    'created' => false,
                    'inserted_id' => null,
                ],
                'warnings' => ['Database mutations are disabled for the current environment or configuration.'],
                'meta' => [
                    'module' => 'database',
                    'read_only' => false,
                    'environment' => $environment,
                ],
            ]);
        }

        if (! $mutationPolicy->allowsTable($validated['table'])) {
            return $this->auditedResponse($this->name(), $request, [
                'summary' => 'Database create request was denied.',
                'data' => [
                    'allowed' => false,
                    'table' => $validated['table'],
                    'created' => false,
                    'inserted_id' => null,
                ],
                'warnings' => ['Table is not allowlisted for MCP database mutations.'],
                'meta' => [
                    'module' => 'database',
                    'read_only' => false,
                    'environment' => $environment,
                ],
            ]);
        }

        $result = $mutator->create($validated['table'], $validated['record']);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Database record created.',
            'data' => array_merge(['allowed' => true], $result),
            'warnings' => [],
            'meta' => [
                'module' => 'database',
                'read_only' => false,
                'environment' => $environment,
            ],
        ]);
    }
}
