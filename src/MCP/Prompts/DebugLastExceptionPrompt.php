<?php

namespace LaravelMcpSuite\MCP\Prompts;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;

#[Description('Guide the client through debugging the last exception.')]
class DebugLastExceptionPrompt extends Prompt
{
    protected string $name = 'debug-last-exception';

    protected ?array $meta = [
        'module' => 'core',
    ];

    public function handle(): ResponseFactory
    {
        return Response::make(
            Response::text(
                'Start with laravel://app/errors/recent, then inspect laravel_exception_last, then review laravel_logs_recent before proposing a diagnosis.'
            )->asAssistant()
        );
    }
}
