<?php

namespace LaravelMcpSuite\MCP\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Resource;
use LaravelMcpSuite\Support\SchemaInspector;

#[Description('Read-only schema overview for the Laravel application database.')]
class SchemaResource extends Resource
{
    protected string $name = 'schema';

    protected string $uri = 'laravel://db/schema';

    protected string $mimeType = 'application/json';

    public function handle(Request $request, SchemaInspector $inspector): ResponseFactory
    {
        return Response::structured([
            'summary' => 'Database schema resource loaded.',
            'data' => [
                'tables' => $inspector->overview(),
            ],
            'warnings' => [],
            'meta' => [
                'module' => 'database',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
