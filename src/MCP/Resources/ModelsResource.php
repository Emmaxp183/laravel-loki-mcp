<?php

namespace LaravelMcpSuite\MCP\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Resource;
use LaravelMcpSuite\Support\ModelInspector;

#[Description('Read-only model inventory for the Laravel application.')]
class ModelsResource extends Resource
{
    protected string $name = 'models';

    protected string $uri = 'laravel://app/models';

    protected string $mimeType = 'application/json';

    public function handle(Request $request, ModelInspector $inspector): ResponseFactory
    {
        return Response::structured([
            'summary' => 'Laravel model resource loaded.',
            'data' => [
                'models' => $inspector->list(),
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
