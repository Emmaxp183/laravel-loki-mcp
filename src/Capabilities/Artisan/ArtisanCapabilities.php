<?php

namespace LaravelMcpSuite\Capabilities\Artisan;

use LaravelMcpSuite\MCP\Tools\LaravelArtisanCommandsTool;
use LaravelMcpSuite\MCP\Tools\LaravelArtisanRunSafeTool;

class ArtisanCapabilities
{
    public function tools(): array
    {
        return [
            LaravelArtisanCommandsTool::class,
            LaravelArtisanRunSafeTool::class,
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
