<?php

namespace LaravelMcpSuite\Capabilities\Logs;

use LaravelMcpSuite\MCP\Resources\RecentErrorsResource;
use LaravelMcpSuite\MCP\Tools\LaravelLogsRecentTool;

class LogsCapabilities
{
    public function tools(): array
    {
        return [
            LaravelLogsRecentTool::class,
        ];
    }

    public function resources(): array
    {
        return [
            RecentErrorsResource::class,
        ];
    }

    public function prompts(): array
    {
        return [];
    }
}
