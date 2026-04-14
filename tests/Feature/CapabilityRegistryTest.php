<?php

namespace LaravelMcpSuite\Tests\Feature;

use Laravel\Mcp\Server\Transport\FakeTransporter;
use LaravelMcpSuite\MCP\Servers\LaravelAppServer;
use LaravelMcpSuite\Support\CapabilityRegistry;
use LaravelMcpSuite\Tests\TestCase;

class CapabilityRegistryTest extends TestCase
{
    public function test_it_returns_enabled_capabilities_for_the_server(): void
    {
        $registry = $this->app->make(CapabilityRegistry::class);

        $this->assertContains(\LaravelMcpSuite\MCP\Tools\LaravelAppInfoTool::class, $registry->tools());
        $this->assertContains(\LaravelMcpSuite\MCP\Tools\LaravelDbRecordCreateTool::class, $registry->tools());
        $this->assertContains(\LaravelMcpSuite\MCP\Tools\LaravelDbRecordUpdateTool::class, $registry->tools());
        $this->assertContains(\LaravelMcpSuite\MCP\Tools\LaravelDbRecordDeleteTool::class, $registry->tools());
        $this->assertContains(\LaravelMcpSuite\MCP\Tools\LaravelStorageListTool::class, $registry->tools());
        $this->assertContains(\LaravelMcpSuite\MCP\Tools\LaravelCrudApiGenerateTool::class, $registry->tools());
        $this->assertContains(\LaravelMcpSuite\MCP\Tools\LaravelCrudWebGenerateTool::class, $registry->tools());
        $this->assertContains(\LaravelMcpSuite\MCP\Resources\RoutesResource::class, $registry->resources());
        $this->assertContains(\LaravelMcpSuite\MCP\Prompts\DebugLastExceptionPrompt::class, $registry->prompts());
    }

    public function test_server_resolves_with_registry_backed_capabilities(): void
    {
        $server = new LaravelAppServer(new FakeTransporter(), $this->app->make(CapabilityRegistry::class));
        $context = $server->createContext();

        $this->assertSame('laravel-app-info', $context->tools()->first()->name());
        $this->assertSame('routes', $context->resources()->first()->name());
        $this->assertSame('debug-last-exception', $context->prompts()->first()->name());
    }
}
