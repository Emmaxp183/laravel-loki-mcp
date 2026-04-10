<?php

namespace LaravelMcpSuite\MCP\Servers;

use Laravel\Mcp\Server\Contracts\Transport;
use LaravelMcpSuite\Support\CapabilityRegistry;

class LaravelAppServer extends \Laravel\Mcp\Server
{
    public function __construct(
        Transport $transport,
        CapabilityRegistry $registry,
    ) {
        parent::__construct($transport);

        $this->tools = $registry->tools();
        $this->resources = $registry->resources();
        $this->prompts = $registry->prompts();
    }
}
