# Installation

## Requirements

- PHP `8.3+`
- Laravel `13.x`
- Composer `2.2+`
- A Laravel application where `php artisan` works normally

## Install The Package

```bash
composer require emmanuelmensah/laravel-mcp-suite
php artisan mcp:install
```

The package auto-discovers its service provider through Composer, so no manual provider registration is required in a normal Laravel application.

## What `mcp:install` Creates

`php artisan mcp:install` is intentionally conservative. It only creates files that do not already exist.

Generated or published files:

- `config/laravel-mcp.php`
- `config/mcp.php`
- `routes/ai.php`
- `docs/project-conventions.md`

Console output:

- a `Codex CLI` local MCP snippet
- a `Claude Code` local MCP snippet
- a short web-mode checklist

## Generated Route Registration

The generated `routes/ai.php` file delegates route setup to `LaravelMcpSuite\Support\AiRouteRegistrar`.

That registrar always registers the local stdio MCP handle and only registers the HTTP endpoint when:

```php
'server' => [
    'enable_web_server' => true,
],
```

## Default Configuration Shape

The important top-level sections in `config/laravel-mcp.php` are:

- `server`
- `modules`
- `write_tools`
- `artisan`
- `file_tools`
- `redaction`

The most important defaults are:

- local MCP handle: `app`
- web MCP path: `/mcp/app`
- web transport: disabled
- write tools: enabled automatically only in `local`
- source file editing: disabled until explicitly enabled

## Local MCP Startup

The package is designed to start with local stdio MCP first.

The generated local handle is `app`, so the underlying launch command is:

```bash
php artisan mcp:start app
```

Normally your MCP client launches that for you. You only need to run it yourself when smoke-testing the server manually.

## Local Verification

After installation, these are the fastest sanity checks:

```bash
php artisan mcp:start app
```

You should also confirm that:

- `config/laravel-mcp.php` exists
- `routes/ai.php` exists
- `docs/project-conventions.md` exists

If you later enable HTTP transport, then this becomes a useful extra check:

```bash
php artisan route:list --path=mcp
```

## Optional Web Transport

HTTP transport is opt-in.

To expose a protected MCP endpoint:

1. Set `laravel-mcp.server.enable_web_server` to `true`
2. Set `LARAVEL_MCP_SHARED_TOKEN`
3. Keep `laravel-mcp.server.auth.mode` as `shared_token`
4. Reload your Laravel app so the route configuration is picked up

The generated endpoint is:

```text
/mcp/app
```

The registrar applies:

- the middleware listed in `laravel-mcp.server.web_middleware`
- the built-in shared-token middleware when auth mode is `shared_token`

Requests must send either:

- `Authorization: Bearer <token>`
- the configured shared token header, default: `X-MCP-Token`

## Optional Passport OAuth Mode

If you want OAuth metadata routes for desktop clients:

1. Install `laravel/passport`
2. Set `laravel-mcp.server.auth.mode` to `passport_oauth`
3. Review `config/mcp.php`
4. Allow the redirect domains and custom schemes your client needs

The suite only registers OAuth metadata routes when Passport is installed and the auth mode is set to `passport_oauth`.

## Optional Source File Editing

The suite includes generic file tools:

- `laravel-files-list`
- `laravel-files-read`
- `laravel-files-patch`
- `laravel-files-write`

Read and list operations work immediately inside approved paths. Patch and write operations stay denied until you explicitly opt in:

```php
'file_tools' => [
    'allow_code_edits' => true,
],
```

Even after opt-in, writes are still restricted to approved paths such as:

- `app/`
- `routes/`
- `database/`
- `config/`
- `tests/`

## Troubleshooting

If `php artisan mcp:install` is not available:

- run `composer dump-autoload`
- confirm the package is installed in `composer.json`
- confirm Laravel package discovery is enabled

If your client cannot connect locally:

- make sure `php artisan mcp:start app` works manually
- make sure the client is running from the Laravel project root
- make sure `routes/ai.php` exists and has not been removed

If HTTP transport does not appear:

- verify `enable_web_server` is `true`
- clear cached config and routes if your app caches them
- verify the request is targeting `/mcp/app`
