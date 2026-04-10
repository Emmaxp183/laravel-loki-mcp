<?php

namespace LaravelMcpSuite\Capabilities\Core;

use LaravelMcpSuite\MCP\Prompts\DebugLastExceptionPrompt;
use LaravelMcpSuite\MCP\Prompts\GenerateFeatureTestPrompt;
use LaravelMcpSuite\MCP\Prompts\ReviewRouteControllerConsistencyPrompt;
use LaravelMcpSuite\MCP\Prompts\ScaffoldCrudPrompt;
use LaravelMcpSuite\MCP\Resources\ProjectConventionsResource;
use LaravelMcpSuite\MCP\Resources\ModelsResource;
use LaravelMcpSuite\MCP\Resources\RoutesResource;
use LaravelMcpSuite\MCP\Tools\LaravelAppInfoTool;
use LaravelMcpSuite\MCP\Tools\LaravelConfigSummaryTool;
use LaravelMcpSuite\MCP\Tools\LaravelExceptionLastTool;
use LaravelMcpSuite\MCP\Tools\LaravelModelDescribeTool;
use LaravelMcpSuite\MCP\Tools\LaravelModelsListTool;
use LaravelMcpSuite\MCP\Tools\LaravelRoutesListTool;

class CoreCapabilities
{
    public function tools(): array
    {
        return [
            LaravelAppInfoTool::class,
            LaravelModelsListTool::class,
            LaravelModelDescribeTool::class,
            LaravelExceptionLastTool::class,
            LaravelConfigSummaryTool::class,
            LaravelRoutesListTool::class,
        ];
    }

    public function resources(): array
    {
        return [
            RoutesResource::class,
            ModelsResource::class,
            ProjectConventionsResource::class,
        ];
    }

    public function prompts(): array
    {
        return [
            DebugLastExceptionPrompt::class,
            ScaffoldCrudPrompt::class,
            GenerateFeatureTestPrompt::class,
            ReviewRouteControllerConsistencyPrompt::class,
        ];
    }
}
