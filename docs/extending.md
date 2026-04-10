# Extending The Package

`Laravel MCP Suite` is meant to be extended at the Laravel application layer. The package gives you a base server, capability registry, route registrar, policies, and sanitization helpers. Your application can swap in extra tools, resources, prompts, and route/auth behavior as needed.

## The Main Extension Points

The most important extension seams are:

- `config/laravel-mcp.php`
- `routes/ai.php`
- custom MCP `Tool` classes
- custom MCP `Resource` classes
- custom MCP `Prompt` classes
- your own capability module or registry layer

## Configuration Overrides

The fastest way to customize behavior is through `config/laravel-mcp.php`.

Useful overrides include:

- enable or disable modules
- change the safe Artisan allowlist
- change write-tool behavior outside `local`
- change HTTP middleware for the web transport
- switch between `shared_token`, custom middleware, and `passport_oauth`
- change approved and blocked file-edit paths
- enable source-file editing

Example:

```php
return [
    'modules' => [
        'core' => true,
        'artisan' => true,
        'database' => true,
        'files' => true,
        'logs' => true,
        'tests' => true,
        'queues' => false,
        'generators' => false,
    ],
    'artisan' => [
        'allowlist' => [
            'about',
            'route:list',
            'test',
        ],
    ],
    'file_tools' => [
        'allow_code_edits' => true,
        'writable_paths' => ['app', 'routes', 'tests'],
        'blocked_paths' => ['.env', 'vendor', 'storage'],
    ],
];
```

## Adding A Custom Tool

Create a class that extends `Laravel\Mcp\Server\Tool`.

Typical responsibilities:

- define a stable tool name
- define an input schema
- validate the request
- perform the action
- return structured MCP output

Minimal example:

```php
<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class AppHealthTool extends Tool
{
    protected string $name = 'app-health';

    public function handle(Request $request): ResponseFactory
    {
        return Response::structured([
            'summary' => 'Application health loaded.',
            'data' => [
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
            ],
            'warnings' => [],
            'meta' => [
                'module' => 'custom',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
```

## Adding A Custom Resource

Create a class that extends `Laravel\Mcp\Server\Resource`.

Resources are best for read-only structured context that should be addressable by URI.

Minimal example:

```php
<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Resource;

class ArchitectureResource extends Resource
{
    protected string $name = 'architecture';

    protected string $uri = 'laravel://docs/architecture';

    protected string $mimeType = 'application/json';

    public function handle(Request $request): ResponseFactory
    {
        return Response::structured([
            'summary' => 'Architecture notes loaded.',
            'data' => [
                'services' => ['billing', 'notifications'],
            ],
            'warnings' => [],
            'meta' => [
                'module' => 'custom',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
```

## Adding A Custom Prompt

Create a class that extends `Laravel\Mcp\Server\Prompt`.

Prompts should guide the client toward the right tools and resources. They should not hide side effects or behave like a magic executor.

Minimal example:

```php
<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Prompt;

class ReviewPoliciesPrompt extends Prompt
{
    protected string $name = 'review-policies';

    public function handle(): ResponseFactory
    {
        return Response::make(
            Response::text(
                'Inspect laravel://app/routes, then review related models and policies before suggesting authorization changes.'
            )->asAssistant()
        );
    }
}
```

## Wiring Custom Primitives Into The Server

There are two clean ways to do this.

### Option 1: Replace `routes/ai.php` Registration

The generated `routes/ai.php` file calls `LaravelMcpSuite\Support\AiRouteRegistrar`. If you need a fully custom server registration flow, replace it with your own bootstrap and keep the package server class or your own derived server:

```php
use Laravel\Mcp\Facades\Mcp;
use LaravelMcpSuite\MCP\Servers\LaravelAppServer;

Mcp::local('app', LaravelAppServer::class);
Mcp::web('/mcp/app', LaravelAppServer::class)->middleware(['api', 'auth:sanctum']);
```

### Option 2: Extend The Registry Layer

If you want to keep the package route registrar but add extra tools, resources, or prompts, create your own registry or your own server class that merges the package registry with application-specific primitives.

That is the cleaner approach when:

- you want to keep package defaults
- you only need to add a few app-specific capabilities
- you do not want to fork the whole MCP route/bootstrap path

## Extending Safety

The package safety posture is configurable, and you can make it stricter in your app.

Common customizations:

- reduce the writable file roots
- disable file editing entirely
- narrow the Artisan allowlist
- add custom auth middleware on HTTP routes
- keep write tools off in every non-local environment

## Recommended Pattern

For most projects, the cleanest extension model is:

1. keep the package config as the base
2. keep `AiRouteRegistrar` unless you need a different transport/auth shape
3. add custom tools/resources/prompts in `App\Mcp\...`
4. register them through a custom server or registry merge
5. keep your write and file-edit rules stricter than your read rules
