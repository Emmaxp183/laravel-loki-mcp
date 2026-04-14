<?php

namespace LaravelMcpSuite\Capabilities\Storage;

use LaravelMcpSuite\MCP\Tools\LaravelStorageListTool;
use LaravelMcpSuite\MCP\Tools\LaravelStorageDeleteTool;
use LaravelMcpSuite\MCP\Tools\LaravelStorageReadTool;
use LaravelMcpSuite\MCP\Tools\LaravelStorageWriteTool;

class StorageCapabilities
{
    public function tools(): array
    {
        return [
            LaravelStorageListTool::class,
            LaravelStorageReadTool::class,
            LaravelStorageWriteTool::class,
            LaravelStorageDeleteTool::class,
        ];
    }

    public function resources(): array
    {
        return [];
    }

    public function prompts(): array
    {
        return [];
    }
}
