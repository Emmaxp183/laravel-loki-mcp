# Safety Model

`Laravel MCP Suite` is designed to inspect Laravel apps safely. It is read-first, and the default settings are intentionally restrictive.

## Default Posture

Default behavior:

- read tools are enabled in supported environments
- write-capable tools are enabled automatically only in `local`
- source-file editing is enabled by default in `local`
- HTTP transport is disabled by default
- shared-token auth is the default HTTP auth mode
- non-allowlisted Artisan commands are rejected

## Environment Rules

The built-in environment policy changes behavior by environment:

- `local`: read tools enabled, write tools can be enabled
- `testing`: read tools enabled, write tools disabled by default
- `staging`: read tools enabled, write tools disabled by default
- `production`: read tools enabled, write tools disabled by default

The two write toggles are:

```php
'write_tools' => [
    'enabled_in_local' => true,
    'enabled_elsewhere' => false,
],
```

These settings affect generator-style tools, safe Artisan execution, and file editing.

## HTTP Transport Safety

The package does not expose an HTTP MCP endpoint until you enable it:

```php
'server' => [
    'enable_web_server' => true,
],
```

When enabled, the default auth mode is `shared_token`. Requests must include one of:

- `Authorization: Bearer <token>`
- `X-MCP-Token: <token>` or whatever header you configure

Relevant settings:

```php
'server' => [
    'auth' => [
        'mode' => 'shared_token',
        'shared_token' => env('LARAVEL_MCP_SHARED_TOKEN'),
        'shared_token_header' => env('LARAVEL_MCP_SHARED_TOKEN_HEADER', 'X-MCP-Token'),
    ],
],
```

If you need OAuth discovery metadata for desktop clients, switch to `passport_oauth` and install `laravel/passport`.

## Safe Artisan Execution

The package does not expose unrestricted `artisan` execution.

The safe command runner only allows commands in:

```php
'artisan' => [
    'allowlist' => [
        'about',
        'route:list',
        'test',
        'db:seed',
        'migrate:status',
        'queue:failed',
        'tinker',
    ],
],
```

Commands such as `migrate`, `db:wipe`, or arbitrary custom commands are not runnable unless you add them yourself.

## Source File Editing

The file tools are split into two groups:

Read-oriented:

- `laravel-files-list`
- `laravel-files-read`

Write-oriented:

- `laravel-files-patch`
- `laravel-files-write`

Write-oriented file tools require both:

- a write-enabled environment
- `laravel-mcp.file_tools.allow_code_edits = true`

Current default:

```php
'file_tools' => [
    'allow_code_edits' => true,
],
```

If you want read-only behavior even in `local`, set that flag to `false`.

## File Path Restrictions

Even with file editing enabled, the path policy still limits what can be changed.

Default writable roots:

- `app/`
- `routes/`
- `database/`
- `config/`
- `tests/`

Default blocked paths:

- `.env`
- `vendor/`
- `storage/`
- `bootstrap/cache/`
- `node_modules/`

The policy also rejects traversal patterns such as `../`.

## Sanitization

The package sanitizes tool output before returning it.

The sanitizer targets values that commonly leak secrets or credentials, including:

- passwords
- tokens
- API keys
- cookies
- DSN secrets
- private keys
- `.env`-style assignments

This matters most for:

- log-reading tools
- exception summaries
- config summaries

## Audit Logging

Every MCP tool call is audit logged to:

```text
storage/logs/laravel-mcp-audit.log
```

Each entry records:

- timestamp
- tool name
- environment
- allowed or denied result
- argument summary

This is especially important for:

- safe Artisan execution
- file patching
- file writing

## Recommended Production Posture

If you expose the suite beyond local development, the safest baseline is:

- keep write tools disabled outside `local`
- keep file editing disabled
- use HTTP transport only when you need it
- use shared-token or Passport auth, never an open web route
- keep the Artisan allowlist short
- review audit logs regularly
