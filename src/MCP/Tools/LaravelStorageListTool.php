<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Support\StorageEditor;

#[Description('List storage objects on an allowlisted Laravel disk.')]
class LaravelStorageListTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-storage-list';

    public function schema(JsonSchema $schema): array
    {
        return [
            'disk' => $schema->string()->required()->description('Allowlisted Laravel storage disk name.'),
            'path' => $schema->string()->required()->description('Allowlisted storage path prefix to list.'),
        ];
    }

    public function handle(Request $request, StorageEditor $editor): ResponseFactory
    {
        $validated = $request->validate([
            'disk' => ['required', 'string'],
            'path' => ['required', 'string'],
        ]);

        $result = $editor->list($validated['disk'], $validated['path']);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => $result['allowed'] ? 'Storage objects listed.' : 'Storage list request was denied.',
            'data' => $result,
            'warnings' => $result['allowed'] ? [] : ['Disk or path is outside the approved MCP storage policy.'],
            'meta' => [
                'module' => 'storage',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
