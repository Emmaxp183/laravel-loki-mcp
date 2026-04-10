<?php

namespace LaravelMcpSuite\MCP\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;

#[Description('Return a safe summary of application configuration.')]
class LaravelConfigSummaryTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-config-summary';

    public function handle(Request $request): ResponseFactory
    {
        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Configuration summary loaded.',
            'data' => [
                'app' => [
                    'environment' => config('app.env'),
                    'debug' => (bool) config('app.debug'),
                ],
                'database' => [
                    'default_driver' => config('database.connections.'.config('database.default').'.driver'),
                ],
                'cache' => [
                    'default_driver' => config('cache.default'),
                ],
                'queue' => [
                    'default_driver' => config('queue.default'),
                ],
                'mail' => [
                    'default_driver' => config('mail.default'),
                ],
            ],
            'warnings' => [],
            'meta' => [
                'module' => 'core',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
