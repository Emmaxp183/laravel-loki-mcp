<?php

namespace LaravelMcpSuite\MCP\Prompts;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;

#[Description('Guide the client through safe CRUD scaffolding.')]
class ScaffoldCrudPrompt extends Prompt
{
    protected string $name = 'scaffold-crud';

    public function handle(): ResponseFactory
    {
        return Response::make(
            Response::text(
                'Inspect laravel://app/routes, laravel://app/models, and laravel://docs/project-conventions before using any generator or write-capable capability to scaffold CRUD.'
            )->asAssistant()
        );
    }
}
