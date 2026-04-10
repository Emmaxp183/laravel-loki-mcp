# Laravel MCP Suite

`Laravel MCP Suite` is a package-first capability suite built on top of [`laravel/mcp`](https://github.com/laravel/mcp). It gives MCP clients a safe, structured way to inspect a Laravel application through tools, resources, and prompts.

## What It Exposes

- Tools for app info, routes, schema, logs, models, config, tests, and safe Artisan actions
- Tools for approved source file listing, reading, patching, and writing
- Resources for routes, models, schema, recent errors, and project conventions
- Prompts for debugging, CRUD scaffolding, feature test generation, and route-controller review

## Install

```bash
composer require emmanuelmensah/laravel-mcp-suite
php artisan mcp:install
```

The install command creates:

- `config/laravel-mcp.php`
- `config/mcp.php`
- `routes/ai.php`
- `docs/project-conventions.md`

It also prints ready-to-use `Codex CLI` and `Claude Code` client snippets plus a web-mode quick start.

## Safety Defaults

- Read tools are enabled by default.
- Write-capable tools are enabled automatically only in `local`.
- Source-file editing stays off until `laravel-mcp.file_tools.allow_code_edits` is enabled.
- HTTP transport is disabled by default.
- When HTTP transport is enabled, the default remote auth mode is a shared token.
- Safe Artisan execution is allowlist-only.
- Tool output is sanitized before it is returned.
- Tool calls are audit logged.

## Web Transport

The package starts with local stdio MCP as the default. If you need a protected HTTP endpoint:

1. Set `laravel-mcp.server.enable_web_server` to `true`
2. Set `LARAVEL_MCP_SHARED_TOKEN`
3. Keep `laravel-mcp.server.auth.mode` as `shared_token`

The generated route registrar will expose the MCP HTTP endpoint and require either `Authorization: Bearer <token>` or the configured shared-token header.

If you want OAuth metadata for desktop clients, install Laravel Passport, switch `laravel-mcp.server.auth.mode` to `passport_oauth`, and review `config/mcp.php`.

## Source File Editing

The suite now exposes generic file tools for any connected client:

- `laravel-files-list`
- `laravel-files-read`
- `laravel-files-patch`
- `laravel-files-write`

Write operations remain denied until you set:

```php
'file_tools' => [
    'allow_code_edits' => true,
],
```

Even then, writes are still restricted to approved directories such as `app/`, `routes/`, `database/`, `config/`, and `tests/`.

## Documentation

- [Installation](docs/installation.md)
- [Safety Model](docs/safety-model.md)
- [Codex CLI Setup](docs/clients/codex-cli.md)
- [Claude Code Setup](docs/clients/claude-code.md)
- [Extending The Package](docs/extending.md)
