<?php

namespace LaravelMcpSuite\Capabilities\Queues;

use LaravelMcpSuite\MCP\Tools\LaravelQueueFailedDeleteTool;
use LaravelMcpSuite\MCP\Tools\LaravelQueueFailedListTool;
use LaravelMcpSuite\MCP\Tools\LaravelQueueFailedRetryTool;

class QueuesCapabilities
{
    public function tools(): array
    {
        return [
            LaravelQueueFailedListTool::class,
            LaravelQueueFailedRetryTool::class,
            LaravelQueueFailedDeleteTool::class,
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
