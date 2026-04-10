<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\FileEditor;

#[Description('Write Laravel source files inside approved directories and return a diff preview.')]
class LaravelFilesWriteTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-files-write';

    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()->required()->description('Relative path to an approved Laravel source file.'),
            'content' => $schema->string()->required()->description('Full file content to write.'),
        ];
    }

    public function handle(Request $request, FileEditor $editor, EnvironmentPolicy $environmentPolicy): ResponseFactory
    {
        $validated = $request->validate([
            'path' => ['required', 'string'],
            'content' => ['required', 'string'],
        ]);

        $environment = (string) config('app.env', app()->environment());

        if (! $environmentPolicy->codeEditsEnabled($environment)) {
            return $this->auditedResponse($this->name(), $request, [
                'summary' => 'File write request was denied.',
                'data' => [
                    'allowed' => false,
                    'path' => $validated['path'],
                    'diff_preview' => null,
                ],
                'warnings' => ['Code edits are disabled for the current environment or configuration.'],
                'meta' => [
                    'module' => 'files',
                    'read_only' => false,
                    'environment' => $environment,
                ],
            ]);
        }

        $result = $editor->write($validated['path'], $validated['content']);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => $result['allowed'] ? 'Project file written.' : 'File write request was denied.',
            'data' => $result,
            'warnings' => $result['allowed'] ? [] : ['Path is outside the approved MCP file access policy.'],
            'meta' => [
                'module' => 'files',
                'read_only' => false,
                'environment' => $environment,
            ],
        ]);
    }
}
