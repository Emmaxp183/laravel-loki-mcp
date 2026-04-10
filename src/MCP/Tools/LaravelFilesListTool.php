<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Support\FileEditor;

#[Description('List Laravel project files inside approved source directories.')]
class LaravelFilesListTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-files-list';

    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()->required()->description('Relative directory inside an approved Laravel source path.'),
            'depth' => $schema->integer()->description('Maximum nested directory depth to include.'),
        ];
    }

    public function handle(Request $request, FileEditor $editor): ResponseFactory
    {
        $validated = $request->validate([
            'path' => ['required', 'string'],
            'depth' => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);

        $result = $editor->list($validated['path'], $validated['depth'] ?? 3);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => $result['allowed'] ? 'Project files listed.' : 'File listing request was denied.',
            'data' => $result,
            'warnings' => $result['allowed'] ? [] : ['Path is outside the approved MCP file access policy.'],
            'meta' => [
                'module' => 'files',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
