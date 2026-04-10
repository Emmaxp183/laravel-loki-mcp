<?php

namespace LaravelMcpSuite\MCP\Prompts;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;

#[Description('Guide the client through reviewing route and controller consistency.')]
class ReviewRouteControllerConsistencyPrompt extends Prompt
{
    protected string $name = 'review-route-controller-consistency';

    public function handle(): ResponseFactory
    {
        return Response::make(
            Response::text(
                'Read laravel://app/routes first, then compare route names, actions, and controller mappings for consistency.'
            )->asAssistant()
        );
    }
}
