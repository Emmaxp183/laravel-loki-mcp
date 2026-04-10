<?php

namespace LaravelMcpSuite\Capabilities\Database;

use LaravelMcpSuite\MCP\Resources\SchemaResource;
use LaravelMcpSuite\MCP\Tools\LaravelDbSchemaReadTool;

class DatabaseCapabilities
{
    public function tools(): array
    {
        return [
            LaravelDbSchemaReadTool::class,
        ];
    }

    public function resources(): array
    {
        return [
            SchemaResource::class,
        ];
    }

    public function prompts(): array
    {
        return [];
    }
}
