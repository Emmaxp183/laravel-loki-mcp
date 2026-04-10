<?php

namespace LaravelMcpSuite\MCP\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Resource;
use LaravelMcpSuite\Sanitizers\OutputSanitizer;
use LaravelMcpSuite\Support\ExceptionSummarizer;
use LaravelMcpSuite\Support\LogReader;

#[Description('Return recent sanitized exception summaries from Laravel logs.')]
class RecentErrorsResource extends Resource
{
    protected string $name = 'recent-errors';

    protected string $uri = 'laravel://app/errors/recent';

    protected string $mimeType = 'application/json';

    public function handle(Request $request, ExceptionSummarizer $summarizer, LogReader $reader, OutputSanitizer $sanitizer): ResponseFactory
    {
        $exceptions = array_map(
            fn (array $exception): array => $sanitizer->sanitize($exception),
            $summarizer->summarize($reader->recent(200)),
        );

        return Response::structured([
            'summary' => 'Recent exception summaries loaded.',
            'data' => [
                'exceptions' => $exceptions,
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
