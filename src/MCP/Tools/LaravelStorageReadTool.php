<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Support\StorageEditor;

#[Description('Read a text object from an allowlisted Laravel storage disk.')]
class LaravelStorageReadTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-storage-read';

    public function schema(JsonSchema $schema): array
    {
        return [
            'disk' => $schema->string()->required()->description('Allowlisted Laravel storage disk name.'),
            'path' => $schema->string()->required()->description('Allowlisted storage object path.'),
        ];
    }

    public function handle(Request $request, StorageEditor $editor): ResponseFactory
    {
        $validated = $request->validate([
            'disk' => ['required', 'string'],
            'path' => ['required', 'string'],
        ]);

        $result = $editor->read($validated['disk'], $validated['path']);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => $result['allowed'] ? 'Storage object loaded.' : 'Storage read request was denied.',
            'data' => $result,
            'warnings' => $result['allowed'] ? [] : ['Storage object could not be returned under the current MCP storage policy.'],
            'meta' => [
                'module' => 'storage',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
