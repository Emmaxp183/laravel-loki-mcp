# Laravel MCP Suite

`Laravel MCP Suite` adds a Laravel-focused MCP server to your app.

It is built on top of [`laravel/mcp`](https://github.com/laravel/mcp) and gives MCP clients a safe way to inspect your Laravel project, run a small allowlisted set of actions, and read or edit approved files.

GitHub repository: [Emmaxp183/laravel-loki-mcp](https://github.com/Emmaxp183/laravel-loki-mcp)

## What You Get

- Laravel tools for app info, routes, models, schema, record mutations, logs, config, tests, and safe Artisan commands
- File tools for listing, reading, patching, and writing approved project files
- Storage tools for listing, reading, writing, and deleting allowlisted Laravel storage objects
- Generator tools for scaffolding Laravel CRUD APIs and web CRUD flows
- Resources for routes, models, schema, recent errors, and project conventions
- Prompt helpers for debugging, CRUD scaffolding, feature tests, and route/controller review

## Quick Start

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

## Laravel Storage Access

The package also exposes these storage tools:

- `laravel-storage-list`
- `laravel-storage-read`
- `laravel-storage-write`
- `laravel-storage-delete`

These operate on Laravel storage disks, not source files. They stay separate from the source-file editor so runtime storage access can be allowlisted independently.

Default storage config:

```php
'modules' => [
    'storage' => true,
],

'storage_tools' => [
    'allow_writes_in_local' => true,
    'allow_writes_elsewhere' => false,
    'allowed_disks' => ['local'],
    'allowed_prefixes' => [
        'local' => ['mcp/'],
    ],
    'max_bytes' => 262144,
],
```

By default, MCP storage access is limited to the `local` disk under the `mcp/` prefix. Writes and deletes remain local-only unless you explicitly open them up in config.

## Database Record Mutations

The package also exposes these database mutation tools:

- `laravel-db-record-create`
- `laravel-db-record-update`
- `laravel-db-record-delete`

These operate directly on allowlisted tables through the query builder. They do not run raw SQL and they do not dispatch Eloquent model events.

Default database mutation config:

```php
'database_tools' => [
    'allow_mutations_in_local' => true,
    'allow_mutations_elsewhere' => false,
    'allowed_tables' => [],
    'allowed_keys' => ['id'],
    'max_rows_per_call' => 1,
],
```

By default, database mutation requests are denied until you explicitly allow tables in `allowed_tables`. Updates and deletes are also limited to allowlisted key columns.

## CRUD Generators

The package exposes two generator tools:

- `laravel-crud-api-generate`
- `laravel-crud-web-generate`

Both tools stay behind the normal local-only code edit guard. The API generator writes model, migration, requests, API resource, API controller, API route registration, and a feature test. The web generator writes model, migration, requests, web controller, Blade views, web route registration, and a feature test.

## Documentation

- [Installation](docs/installation.md)
- [Safety Model](docs/safety-model.md)
- [Use With Codex CLI](docs/clients/codex-cli.md)
- [Use With Claude Code](docs/clients/claude-code.md)
- [Extending The Package](docs/extending.md)
