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

#[Description('Write a text object to an allowlisted Laravel storage disk.')]
class LaravelStorageWriteTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-storage-write';

    public function schema(JsonSchema $schema): array
    {
        return [
            'disk' => $schema->string()->required()->description('Allowlisted Laravel storage disk name.'),
            'path' => $schema->string()->required()->description('Allowlisted storage object path.'),
            'content' => $schema->string()->required()->description('Text content to write.'),
            'overwrite' => $schema->boolean()->description('Whether to replace an existing storage object.'),
        ];
    }

    public function handle(Request $request, StorageEditor $editor, EnvironmentPolicy $environmentPolicy): ResponseFactory
    {
        $validated = $request->validate([
            'disk' => ['required', 'string'],
            'path' => ['required', 'string'],
            'content' => ['required', 'string'],
            'overwrite' => ['nullable', 'boolean'],
        ]);

        $environment = (string) config('app.env', app()->environment());

        if (! $environmentPolicy->storageWritesEnabled($environment)) {
            return $this->auditedResponse($this->name(), $request, [
                'summary' => 'Storage write request was denied.',
                'data' => [
                    'allowed' => false,
                    'disk' => $validated['disk'],
                    'path' => $validated['path'],
                    'bytes' => null,
                    'overwritten' => false,
                ],
                'warnings' => ['Storage writes are disabled for the current environment or configuration.'],
                'meta' => [
                    'module' => 'storage',
                    'read_only' => false,
                    'environment' => $environment,
                ],
            ]);
        }

        $result = $editor->write(
            $validated['disk'],
            $validated['path'],
            $validated['content'],
            (bool) ($validated['overwrite'] ?? false),
        );

        return $this->auditedResponse($this->name(), $request, [
            'summary' => $result['allowed'] ? 'Storage object written.' : 'Storage write request was denied.',
            'data' => $result,
            'warnings' => $result['allowed'] ? [] : ['Storage object could not be written under the current MCP storage policy.'],
            'meta' => [
                'module' => 'storage',
                'read_only' => false,
                'environment' => $environment,
            ],
        ]);
    }
}
