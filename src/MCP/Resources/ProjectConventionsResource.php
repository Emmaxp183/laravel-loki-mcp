<?php

namespace LaravelMcpSuite\MCP\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Resource;

#[Description('Read the application project conventions markdown.')]
class ProjectConventionsResource extends Resource
{
    protected string $name = 'project-conventions';

    protected string $uri = 'laravel://docs/project-conventions';

    protected string $mimeType = 'text/markdown';

    public function handle(Request $request): ResponseFactory
    {
        $path = base_path('docs/project-conventions.md');

        if (! is_file($path)) {
            $path = dirname(__DIR__, 3).'/resources/project-conventions/default.md';
        }

        return Response::structured([
            'summary' => 'Project conventions loaded.',
            'data' => [
                'markdown' => (string) file_get_contents($path),
            ],
            'warnings' => [],
            'meta' => [
                'module' => 'core',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
