<?php

namespace LaravelMcpSuite\Capabilities\Tests;

use LaravelMcpSuite\MCP\Tools\LaravelTestsRunTool;

class TestsCapabilities
{
    public function tools(): array
    {
        return [
            LaravelTestsRunTool::class,
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
