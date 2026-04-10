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

#[Description('Apply a search-and-replace patch to an approved Laravel source file.')]
class LaravelFilesPatchTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-files-patch';

    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()->required()->description('Relative path to an approved Laravel source file.'),
            'search' => $schema->string()->required()->description('Exact text to replace.'),
            'replace' => $schema->string()->required()->description('Replacement text.'),
        ];
    }

    public function handle(Request $request, FileEditor $editor, EnvironmentPolicy $environmentPolicy): ResponseFactory
    {
        $validated = $request->validate([
            'path' => ['required', 'string'],
            'search' => ['required', 'string'],
            'replace' => ['required', 'string'],
        ]);

        $environment = (string) config('app.env', app()->environment());

        if (! $environmentPolicy->codeEditsEnabled($environment)) {
            return $this->auditedResponse($this->name(), $request, [
                'summary' => 'File patch request was denied.',
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

        $result = $editor->patch($validated['path'], $validated['search'], $validated['replace']);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => $result['allowed'] ? 'Project file patched.' : 'File patch request was denied.',
            'data' => $result,
            'warnings' => $result['allowed'] ? [] : ['Patch could not be applied inside the approved MCP file access policy.'],
            'meta' => [
                'module' => 'files',
                'read_only' => false,
                'environment' => $environment,
            ],
        ]);
    }
}
