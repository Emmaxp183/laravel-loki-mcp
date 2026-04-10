<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Sanitizers\OutputSanitizer;
use LaravelMcpSuite\Support\LogReader;

#[Description('Return sanitized recent Laravel log entries.')]
class LaravelLogsRecentTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-logs-recent';

    public function schema(JsonSchema $schema): array
    {
        return [
            'lines' => $schema->integer()->min(1)->max(500)->description('Maximum number of recent log lines to read.'),
            'level' => $schema->string()->description('Optional log level filter.'),
        ];
    }

    public function handle(Request $request, LogReader $reader, OutputSanitizer $sanitizer): ResponseFactory
    {
        $validated = $request->validate([
            'lines' => ['nullable', 'integer', 'min:1', 'max:500'],
            'level' => ['nullable', 'string'],
        ]);

        $entries = array_map(
            fn (array $entry): array => $sanitizer->sanitize($entry),
            $reader->recent($validated['lines'] ?? 100, $validated['level'] ?? null),
        );

        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Recent log entries loaded.',
            'data' => [
                'entries' => $entries,
            ],
            'warnings' => [],
            'meta' => [
                'module' => 'logs',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
