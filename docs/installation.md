# Installation

This guide shows the shortest path to a working Laravel MCP server.

## Requirements

- PHP `8.3+`
- Laravel `13.x`
- Composer `2.2+`
- A Laravel application where `php artisan` works normally

## 1. Install The Package

Until the first tagged release is published, install the package from GitHub:

```bash
composer config repositories.laravel-mcp-suite vcs https://github.com/Emmaxp183/laravel-loki-mcp.git
composer require immaxp/laravel-mcp-suite:dev-main
php artisan mcp:install
```

After the first tagged release is published, this simpler command will work:

```bash
composer require immaxp/laravel-mcp-suite
php artisan mcp:install
```

The package auto-discovers its service provider through Composer, so no manual provider registration is required in a normal Laravel application.

## 2. See What `mcp:install` Creates

`php artisan mcp:install` is intentionally conservative. It only creates files that do not already exist.

Files created or published:

- `config/laravel-mcp.php`
- `config/mcp.php`
- `routes/ai.php`
- `docs/project-conventions.md`

It also prints:

- a `Codex CLI` local MCP snippet
- a `Claude Code` local MCP snippet
- a short web-mode checklist

## 3. Understand The Default Setup

The generated `routes/ai.php` file delegates route setup to `LaravelMcpSuite\Support\AiRouteRegistrar`.

By default:

- the local MCP handle is `app`
- the local command is `php artisan mcp:start app`
- the HTTP endpoint is off
- write-capable tools only work automatically in `local`
- file editing is enabled by default in `local`

The HTTP endpoint is only registered when:

```php
'server' => [
    'enable_web_server' => true,
],
```

Main sections in `config/laravel-mcp.php`:

- `server`
- `modules`
- `write_tools`
- `artisan`
- `file_tools`
- `redaction`

## 4. Verify Local MCP Works

The default local command is:

```bash
php artisan mcp:start app
```

Your MCP client will usually run that for you. Running it manually is the quickest smoke test.

You should also confirm that:

- `config/laravel-mcp.php` exists
- `routes/ai.php` exists
- `docs/project-conventions.md` exists

If you later enable HTTP mode, this is also useful:

```bash
php artisan route:list --path=mcp
```

## 5. Optional: Enable HTTP Mode

HTTP transport is opt-in.

To expose a protected MCP endpoint:

1. Set `laravel-mcp.server.enable_web_server` to `true`
2. Set `LARAVEL_MCP_SHARED_TOKEN`
3. Keep `laravel-mcp.server.auth.mode` as `shared_token`
4. Reload your Laravel app so the route configuration is picked up

Default endpoint:

```text
/mcp/app
```

The registrar applies:

- the middleware listed in `laravel-mcp.server.web_middleware`
- the built-in shared-token middleware when auth mode is `shared_token`

Requests must send either:

- `Authorization: Bearer <token>`
- the configured shared token header, default: `X-MCP-Token`

## 6. Optional: Enable Passport OAuth

If you want OAuth metadata routes for desktop clients:

1. Install `laravel/passport`
2. Set `laravel-mcp.server.auth.mode` to `passport_oauth`
3. Review `config/mcp.php`
4. Allow the redirect domains and custom schemes your client needs

The suite only registers OAuth metadata routes when Passport is installed and the auth mode is set to `passport_oauth`.

## 7. Understand File Editing

The suite includes generic file tools:

- `laravel-files-list`
- `laravel-files-read`
- `laravel-files-patch`
- `laravel-files-write`

Read and list work immediately inside approved paths.

Patch and write also work immediately in `local` because the default config includes:

```php
'file_tools' => [
    'allow_code_edits' => true,
],
```

Those write operations are still denied outside write-enabled environments. You can also turn them off in `local` by setting `allow_code_edits` to `false`.

When writes are allowed, they are still restricted to approved paths such as:

- `app/`
- `routes/`
- `database/`
- `config/`
- `tests/`

## Troubleshooting

If `php artisan mcp:install` is missing:

- run `composer dump-autoload`
- confirm the package is installed in `composer.json`
- confirm Laravel package discovery is enabled

If Composer cannot find `immaxp/laravel-mcp-suite`:

- make sure you added the GitHub VCS repository entry first
- use `composer require immaxp/laravel-mcp-suite:dev-main` until the first tagged release exists

If your client cannot connect locally:

- make sure `php artisan mcp:start app` works manually
- make sure the client is running from the Laravel project root
- make sure `routes/ai.php` exists and has not been removed

If HTTP transport does not appear:

- verify `enable_web_server` is `true`
- clear cached config and routes if your app caches them
- verify the request is targeting `/mcp/app`

If file writes are denied unexpectedly:

- verify the app environment is `local`
- verify `write_tools.enabled_in_local` is `true`
- verify `file_tools.allow_code_edits` is `true`
- verify the target path is inside an approved writable root
