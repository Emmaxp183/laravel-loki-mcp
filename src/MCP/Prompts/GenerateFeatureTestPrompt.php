<?php

namespace LaravelMcpSuite\MCP\Prompts;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;

#[Description('Guide the client through generating a feature test.')]
class GenerateFeatureTestPrompt extends Prompt
{
    protected string $name = 'generate-feature-test';

    public function handle(): ResponseFactory
    {
        return Response::make(
            Response::text(
                'Use laravel://app/routes for endpoint context, then laravel://docs/project-conventions, then generate the test and verify it with laravel_tests_run.'
            )->asAssistant()
        );
    }
}
