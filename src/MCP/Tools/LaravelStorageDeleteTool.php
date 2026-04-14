<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\StorageEditor;

#[Description('Delete an object from an allowlisted Laravel storage disk.')]
class LaravelStorageDeleteTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-storage-delete';

    public function schema(JsonSchema $schema): array
    {
        return [
            'disk' => $schema->string()->required()->description('Allowlisted Laravel storage disk name.'),
            'path' => $schema->string()->required()->description('Allowlisted storage object path.'),
        ];
    }

    public function handle(Request $request, StorageEditor $editor, EnvironmentPolicy $environmentPolicy): ResponseFactory
    {
        $validated = $request->validate([
            'disk' => ['required', 'string'],
            'path' => ['required', 'string'],
        ]);

        $environment = (string) config('app.env', app()->environment());

        if (! $environmentPolicy->storageWritesEnabled($environment)) {
            return $this->auditedResponse($this->name(), $request, [
                'summary' => 'Storage delete request was denied.',
                'data' => [
                    'allowed' => false,
                    'disk' => $validated['disk'],
                    'path' => $validated['path'],
                    'deleted' => false,
                ],
                'warnings' => ['Storage deletes are disabled for the current environment or configuration.'],
                'meta' => [
                    'module' => 'storage',
                    'read_only' => false,
                    'environment' => $environment,
                ],
            ]);
        }

        $result = $editor->delete($validated['disk'], $validated['path']);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => $result['allowed'] ? 'Storage object deleted.' : 'Storage delete request was denied.',
            'data' => $result,
            'warnings' => $result['allowed'] ? [] : ['Storage object could not be deleted under the current MCP storage policy.'],
            'meta' => [
                'module' => 'storage',
                'read_only' => false,
                'environment' => $environment,
            ],
        ]);
    }
}
