<?php

namespace LaravelMcpSuite\Support;

use Laravel\Mcp\Facades\Mcp;
use Laravel\Passport\Passport;
use LaravelMcpSuite\Http\Middleware\EnsureSharedMcpToken;
use LaravelMcpSuite\MCP\Servers\LaravelAppServer;

class AiRouteRegistrar
{
    public function register(): void
    {
        Mcp::local($this->localHandle(), LaravelAppServer::class);

        if (! config('laravel-mcp.server.enable_web_server', false)) {
            return;
        }

        $route = Mcp::web($this->webPath(), LaravelAppServer::class);

        foreach ($this->middleware() as $middleware) {
            $route->middleware($middleware);
        }

        if ($this->usesPassportOauth()) {
            Mcp::oauthRoutes((string) config('laravel-mcp.server.auth.oauth_prefix', 'oauth'));
        }
    }

    protected function localHandle(): string
    {
        return (string) config('laravel-mcp.server.local_command', 'app');
    }

    protected function webPath(): string
    {
        return (string) config('laravel-mcp.server.web_path', '/mcp/app');
    }

    /**
     * @return array<int, string>
     */
    protected function middleware(): array
    {
        $middleware = config('laravel-mcp.server.web_middleware', ['api']);
        $middleware = is_array($middleware) ? $middleware : ['api'];

        if (config('laravel-mcp.server.auth.mode') === 'shared_token') {
            $middleware[] = EnsureSharedMcpToken::class;
        }

        $extra = config('laravel-mcp.server.auth.middleware', []);
        $extra = is_array($extra) ? $extra : [];

        return array_values(array_unique(array_filter([
            ...$middleware,
            ...$extra,
        ])));
    }

    protected function usesPassportOauth(): bool
    {
        return config('laravel-mcp.server.auth.mode') === 'passport_oauth'
            && class_exists(Passport::class);
    }
}
