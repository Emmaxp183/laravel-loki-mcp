<?php

namespace LaravelMcpSuite\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSharedMcpToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('laravel-mcp.server.auth.shared_token', '');
        $providedToken = $this->resolveToken($request);

        if ($expectedToken === '' || $providedToken === null || ! hash_equals($expectedToken, $providedToken)) {
            return new JsonResponse([
                'message' => 'Unauthorized MCP request.',
            ], 401);
        }

        return $next($request);
    }

    protected function resolveToken(Request $request): ?string
    {
        $headerName = (string) config('laravel-mcp.server.auth.shared_token_header', 'X-MCP-Token');
        $headerToken = $request->headers->get($headerName);

        if (is_string($headerToken) && $headerToken !== '') {
            return $headerToken;
        }

        $bearerToken = $request->bearerToken();

        return $bearerToken !== '' ? $bearerToken : null;
    }
}
