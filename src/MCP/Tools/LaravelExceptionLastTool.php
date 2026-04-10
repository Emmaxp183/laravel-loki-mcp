<?php

namespace LaravelMcpSuite\MCP\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Sanitizers\OutputSanitizer;
use LaravelMcpSuite\Support\ExceptionSummarizer;
use LaravelMcpSuite\Support\LogReader;

#[Description('Return the latest sanitized exception summary.')]
class LaravelExceptionLastTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-exception-last';

    public function handle(Request $request, ExceptionSummarizer $summarizer, LogReader $reader, OutputSanitizer $sanitizer): ResponseFactory
    {
        $exception = $summarizer->summarize($reader->recent(200))[0] ?? [
            'type' => 'UnknownException',
            'message' => 'No exception found.',
            'context' => '',
        ];

        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Latest exception loaded.',
            'data' => [
                'exception' => $sanitizer->sanitize($exception),
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
