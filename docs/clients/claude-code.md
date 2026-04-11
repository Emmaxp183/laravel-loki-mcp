# Use With Claude Code

This is the simplest way to connect Claude Code to your Laravel app.

Start with local MCP first. Only move to HTTP if you want a remote endpoint.

## Local MCP Setup

After you run:

```bash
php artisan mcp:install
```

register the server with Claude Code:

```bash
claude mcp add laravel-app -- php artisan mcp:start app
```

This assumes:

- the command runs from the Laravel project root
- the local MCP handle remains `app`

## Recommended Setup Order

The easiest order is:

1. install the package
2. run `php artisan mcp:install`
3. wire the local MCP command
4. verify Claude Code can see the Laravel tool and resource inventory
5. only then consider enabling HTTP transport or code editing

That keeps the first setup simple.

## What Claude Code Can Access

With default modules enabled, Claude Code can discover:

Core inspection tools:

- `laravel-app-info`
- `laravel-routes-list`
- `laravel-models-list`
- `laravel-model-describe`
- `laravel-config-summary`
- `laravel-exception-last`

Operational tools:

- `laravel-db-schema-read`
- `laravel-logs-recent`
- `laravel-tests-run`
- `laravel-artisan-commands`
- `laravel-artisan-run-safe`

File tools:

- `laravel-files-list`
- `laravel-files-read`
- `laravel-files-patch`
- `laravel-files-write`

Resources:

- `laravel://app/routes`
- `laravel://app/models`
- `laravel://db/schema`
- `laravel://app/errors/recent`
- `laravel://docs/project-conventions`

Prompts:

- `debug-last-exception`
- `generate-feature-test`
- `review-route-controller-consistency`
- `scaffold-crud`

## Optional HTTP Mode

If you want Claude Code to talk to a remote Laravel app instead of starting it locally, enable the Laravel-side HTTP server first:

1. set `laravel-mcp.server.enable_web_server` to `true`
2. set `LARAVEL_MCP_SHARED_TOKEN`
3. keep `laravel-mcp.server.auth.mode` as `shared_token`
4. expose `/mcp/app`

Requests must send:

- `Authorization: Bearer <token>`
- or the configured shared-token header

This guide covers the Laravel-side setup. Use the HTTP MCP configuration format required by your Claude Code version.

## Optional Passport OAuth

If your Claude Code setup needs OAuth discovery metadata:

1. install `laravel/passport`
2. set `laravel-mcp.server.auth.mode` to `passport_oauth`
3. configure `config/mcp.php`
4. allow the redirect domains and URI schemes your client needs

Without Passport installed, the suite will not register OAuth metadata routes.

## File Editing

In the current default config, patch and write are already enabled in `local`:

```php
'file_tools' => [
    'allow_code_edits' => true,
],
```

Outside `local`, or if you set that flag to `false`, patch and write are denied.

Even when writes are allowed:

- writes still depend on a write-enabled environment
- blocked paths like `.env` remain denied
- only approved writable roots are editable

## Troubleshooting

If Claude Code cannot connect locally:

- manually run `php artisan mcp:start app`
- confirm you are in the Laravel app root
- verify `routes/ai.php` exists

If remote mode fails:

- verify `enable_web_server` is `true`
- verify the app is serving `/mcp/app`
- verify the auth token is actually being sent

If file writes are denied:

- verify the app environment is `local`
- verify `write_tools.enabled_in_local` is `true`
- verify `file_tools.allow_code_edits` is `true`
- verify the target path is inside an approved writable root
