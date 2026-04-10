# Laravel MCP Suite

`Laravel MCP Suite` adds a Laravel-focused MCP server to your app.

It is built on top of [`laravel/mcp`](https://github.com/laravel/mcp) and gives MCP clients a safe way to inspect your Laravel project, run a small allowlisted set of actions, and read or edit approved files.

GitHub repository: [Emmaxp183/laravel-loki-mcp](https://github.com/Emmaxp183/laravel-loki-mcp)

## What You Get

- Laravel tools for app info, routes, models, schema, logs, config, tests, and safe Artisan commands
- File tools for listing, reading, patching, and writing approved project files
- Resources for routes, models, schema, recent errors, and project conventions
- Prompt helpers for debugging, CRUD scaffolding, feature tests, and route/controller review

## Quick Start

Until the first tagged release is published, install the package from GitHub:

```bash
composer config repositories.laravel-mcp-suite vcs https://github.com/Emmaxp183/laravel-loki-mcp.git
composer require immaxp/laravel-mcp-suite:dev-main
php artisan mcp:install
```

After the first tagged release is published, this will work instead:

```bash
composer require immaxp/laravel-mcp-suite
php artisan mcp:install
```

That command adds the MCP setup files your app needs:

- `config/laravel-mcp.php`
- `config/mcp.php`
- `routes/ai.php`
- `docs/project-conventions.md`

It also prints ready-to-use client snippets for `Codex CLI` and `Claude Code`.

## How It Starts

The package starts in local stdio mode.

The default local MCP command is:

```bash
php artisan mcp:start app
```

Most clients will run that for you.

## Safe By Default

- Read tools are on by default.
- Write-capable tools only work automatically in `local`.
- File editing is on by default in `local`.
- HTTP mode is off by default.
- HTTP mode uses shared-token auth by default.
- Artisan access is allowlist-only.
- Tool output is sanitized.
- Tool calls are audit logged.

## Web Transport

If you need a protected HTTP endpoint:

1. Set `laravel-mcp.server.enable_web_server` to `true`
2. Set `LARAVEL_MCP_SHARED_TOKEN`
3. Keep `laravel-mcp.server.auth.mode` as `shared_token`

The default HTTP endpoint is `/mcp/app`.

Requests must send either `Authorization: Bearer <token>` or the configured shared-token header.

If you want OAuth metadata for desktop clients, install Laravel Passport, switch `laravel-mcp.server.auth.mode` to `passport_oauth`, and review `config/mcp.php`.

## Source File Editing

The package exposes these file tools:

- `laravel-files-list`
- `laravel-files-read`
- `laravel-files-patch`
- `laravel-files-write`

In the current default config, patch and write are enabled in `local` because:

```php
'file_tools' => [
    'allow_code_edits' => true,
],
```

Outside `local`, or if you set that flag to `false`, write requests are denied.

Even when writes are allowed, they are still limited to approved directories such as `app/`, `routes/`, `database/`, `config/`, and `tests/`.

## Documentation

- [Installation](docs/installation.md)
- [Safety Model](docs/safety-model.md)
- [Use With Codex CLI](docs/clients/codex-cli.md)
- [Use With Claude Code](docs/clients/claude-code.md)
- [Extending The Package](docs/extending.md)
