<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;

#[Description('Summarize framework and application metadata.')]
class LaravelAppInfoTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-app-info';

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): ResponseFactory
    {
        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Laravel application metadata loaded.',
            'data' => [
                'app' => [
                    'environment' => app()->environment(),
                    'debug' => (bool) config('app.debug'),
                ],
                'framework' => [
                    'laravel_version' => app()->version(),
                    'php_version' => PHP_VERSION,
                ],
                'integrations' => [
                    'horizon' => class_exists(\Laravel\Horizon\Horizon::class),
                    'telescope' => class_exists(\Laravel\Telescope\Telescope::class),
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
