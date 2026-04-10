<?php

namespace LaravelMcpSuite\Tests\Feature\Http;

use Illuminate\Http\Request;
use LaravelMcpSuite\Http\Middleware\EnsureSharedMcpToken;
use LaravelMcpSuite\Tests\TestCase;

class EnsureSharedMcpTokenTest extends TestCase
{
    public function test_it_allows_requests_with_the_configured_header_token(): void
    {
        config()->set('laravel-mcp.server.auth.shared_token', 'top-secret');
        config()->set('laravel-mcp.server.auth.shared_token_header', 'X-MCP-Token');

        $request = Request::create('/mcp/app', 'POST', server: [
            'HTTP_X_MCP_TOKEN' => 'top-secret',
        ]);

        $response = app(EnsureSharedMcpToken::class)->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_it_allows_requests_with_a_bearer_token(): void
    {
        config()->set('laravel-mcp.server.auth.shared_token', 'top-secret');
        config()->set('laravel-mcp.server.auth.shared_token_header', 'X-MCP-Token');

        $request = Request::create('/mcp/app', 'POST', server: [
            'HTTP_AUTHORIZATION' => 'Bearer top-secret',
        ]);

        $response = app(EnsureSharedMcpToken::class)->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_rejects_requests_without_a_valid_token(): void
    {
        config()->set('laravel-mcp.server.auth.shared_token', 'top-secret');

        $request = Request::create('/mcp/app', 'POST');

        $response = app(EnsureSharedMcpToken::class)->handle($request, fn () => response('ok'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['message' => 'Unauthorized MCP request.'], $response->getData(true));
    }
}
