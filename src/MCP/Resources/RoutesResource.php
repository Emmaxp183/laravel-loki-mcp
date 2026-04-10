<?php

namespace LaravelMcpSuite\MCP\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Resource;
use LaravelMcpSuite\Support\RouteInspector;

#[Description('Provide route context grouped for MCP clients.')]
class RoutesResource extends Resource
{
    protected string $name = 'routes';

    protected string $uri = 'laravel://app/routes';

    protected string $mimeType = 'application/json';

    public function handle(Request $request, RouteInspector $inspector): ResponseFactory
    {
        return Response::structured([
            'summary' => 'Laravel route context loaded.',
            'data' => $inspector->grouped(),
            'warnings' => [],
            'meta' => [
                'module' => 'core',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
