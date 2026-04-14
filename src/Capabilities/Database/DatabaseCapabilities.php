<?php

namespace LaravelMcpSuite\Capabilities\Database;

use LaravelMcpSuite\MCP\Resources\SchemaResource;
use LaravelMcpSuite\MCP\Tools\LaravelDbRecordCreateTool;
use LaravelMcpSuite\MCP\Tools\LaravelDbRecordDeleteTool;
use LaravelMcpSuite\MCP\Tools\LaravelDbSchemaReadTool;
use LaravelMcpSuite\MCP\Tools\LaravelDbRecordUpdateTool;

class DatabaseCapabilities
{
    public function tools(): array
    {
        return [
            LaravelDbSchemaReadTool::class,
            LaravelDbRecordCreateTool::class,
            LaravelDbRecordUpdateTool::class,
            LaravelDbRecordDeleteTool::class,
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
