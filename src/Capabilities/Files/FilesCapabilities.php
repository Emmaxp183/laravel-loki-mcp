<?php

namespace LaravelMcpSuite\Capabilities\Files;

use LaravelMcpSuite\MCP\Tools\LaravelFilesListTool;
use LaravelMcpSuite\MCP\Tools\LaravelFilesPatchTool;
use LaravelMcpSuite\MCP\Tools\LaravelFilesReadTool;
use LaravelMcpSuite\MCP\Tools\LaravelFilesWriteTool;

class FilesCapabilities
{
    public function tools(): array
    {
        return [
            LaravelFilesListTool::class,
            LaravelFilesReadTool::class,
            LaravelFilesPatchTool::class,
            LaravelFilesWriteTool::class,
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
