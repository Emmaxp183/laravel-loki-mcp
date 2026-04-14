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

#[Description('Delete one record from an allowlisted database table.')]
class LaravelDbRecordDeleteTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-db-record-delete';

    public function schema(JsonSchema $schema): array
    {
        return [
            'table' => $schema->string()->required()->description('Allowlisted database table name.'),
            'key' => $schema->string()->required()->description('Allowlisted lookup column name.'),
            'id' => $schema->string()->required()->description('Lookup value for the target row.'),
        ];
    }

    public function handle(Request $request, DatabaseMutator $mutator, DatabaseMutationPolicy $mutationPolicy, EnvironmentPolicy $environmentPolicy): ResponseFactory
    {
        $validated = $request->validate([
            'table' => ['required', 'string'],
            'key' => ['required', 'string'],
            'id' => ['required'],
        ]);

        $environment = (string) config('app.env', app()->environment());

        if (! $environmentPolicy->databaseMutationsEnabled($environment)) {
            return $this->denied($request, $validated, $environment, 'Database mutations are disabled for the current environment or configuration.');
        }

        if (! $mutationPolicy->allowsTable($validated['table'])) {
            return $this->denied($request, $validated, $environment, 'Table is not allowlisted for MCP database mutations.');
        }

        if (! $mutationPolicy->allowsKey($validated['key'])) {
            return $this->denied($request, $validated, $environment, 'Key column is not allowlisted for MCP database mutations.');
        }

        $result = $mutator->delete($validated['table'], $validated['key'], $validated['id']);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Database record deleted.',
            'data' => array_merge(['allowed' => true], $result),
            'warnings' => [],
            'meta' => [
                'module' => 'database',
                'read_only' => false,
                'environment' => $environment,
            ],
        ]);
    }

    protected function denied(Request $request, array $validated, string $environment, string $warning): ResponseFactory
    {
        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Database delete request was denied.',
            'data' => [
                'allowed' => false,
                'table' => $validated['table'],
                'key' => $validated['key'],
                'id' => $validated['id'],
                'affected_rows' => 0,
            ],
            'warnings' => [$warning],
            'meta' => [
                'module' => 'database',
                'read_only' => false,
                'environment' => $environment,
            ],
        ]);
    }
}
