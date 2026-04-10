<?php

namespace LaravelMcpSuite\MCP\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Support\FileEditor;

#[Description('Read Laravel source files from approved directories only.')]
class LaravelFilesReadTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-files-read';

    public function handle(Request $request, FileEditor $editor): ResponseFactory
    {
        $validated = $request->validate([
            'path' => ['required', 'string'],
        ]);

        $result = $editor->read($validated['path']);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => $result['allowed'] ? 'Project file loaded.' : 'File read request was denied.',
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
