<?php

namespace LaravelMcpSuite\Capabilities\Generators;

use LaravelMcpSuite\MCP\Tools\LaravelCrudApiGenerateTool;
use LaravelMcpSuite\MCP\Tools\LaravelCrudWebGenerateTool;

class GeneratorsCapabilities
{
    public function tools(): array
    {
        return [
            LaravelCrudApiGenerateTool::class,
            LaravelCrudWebGenerateTool::class,
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
