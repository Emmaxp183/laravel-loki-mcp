<?php

namespace LaravelMcpSuite\Concerns;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use LaravelMcpSuite\Support\AuditLogger;

trait AuditsToolCalls
{
    protected function auditedResponse(string $tool, Request $request, array $payload): ResponseFactory
    {
        app(AuditLogger::class)->record($tool, $request->toArray(), $payload);

        return Response::structured($payload);
    }
}
