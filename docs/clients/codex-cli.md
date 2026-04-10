# Codex CLI Setup

This guide covers the two supported deployment shapes:

- local stdio MCP, which is the default and recommended starting point
- optional remote HTTP MCP, when you deliberately expose `/mcp/app`

## Local MCP Setup

After running:

```bash
php artisan mcp:install
```

add the generated server entry to your Codex CLI MCP configuration:

```json
{
  "mcpServers": {
    "laravel-app": {
      "command": "php",
      "args": ["artisan", "mcp:start", "app"]
    }
  }
}
```

Important assumptions:

- the command runs from the Laravel project root
- the MCP handle is still `app`
- `routes/ai.php` is present

## What Codex CLI Will See

With the default module set, Codex CLI can discover tools such as:

- `laravel-app-info`
- `laravel-routes-list`
- `laravel-models-list`
- `laravel-model-describe`
- `laravel-db-schema-read`
- `laravel-logs-recent`
- `laravel-exception-last`
- `laravel-config-summary`
- `laravel-artisan-commands`
- `laravel-artisan-run-safe`
- `laravel-tests-run`
- `laravel-files-list`
- `laravel-files-read`
- `laravel-files-patch`
- `laravel-files-write`

It can also discover resources such as:

- `laravel://app/routes`
- `laravel://app/models`
- `laravel://db/schema`
- `laravel://app/errors/recent`
- `laravel://docs/project-conventions`

And prompt helpers such as:

- `debug-last-exception`
- `generate-feature-test`
- `review-route-controller-consistency`
- `scaffold-crud`

## Local Smoke Test

If you want to verify the Laravel side before involving Codex CLI:

```bash
php artisan mcp:start app
```

That confirms the local MCP handle is registered and can boot.

## Remote HTTP Mode

Codex CLI can also talk to the suite over HTTP if your client configuration supports MCP over HTTP. The Laravel-side setup is:

1. Set `laravel-mcp.server.enable_web_server` to `true`
2. Set `LARAVEL_MCP_SHARED_TOKEN`
3. Keep `laravel-mcp.server.auth.mode` as `shared_token`
4. Expose the app at `/mcp/app`

Required request auth:

- `Authorization: Bearer <token>`
- or the configured shared-token header

This package documents the Laravel-side requirements; use the Codex CLI MCP HTTP server format appropriate to your installed client version.

## Passport OAuth Mode

If you need OAuth metadata routes for a desktop flow:

1. Install `laravel/passport`
2. Set `laravel-mcp.server.auth.mode` to `passport_oauth`
3. Review `config/mcp.php`
4. Allow the redirect domains and custom schemes your client needs

The suite only registers OAuth metadata routes when Passport is installed.

## File Editing With Codex CLI

Codex CLI can use the file tools, but writes remain denied until you explicitly opt in:

```php
'file_tools' => [
    'allow_code_edits' => true,
],
```

Even after that, writes stay restricted to approved Laravel source directories and blocked paths like `.env` remain inaccessible.

## Troubleshooting

If Codex CLI cannot start the server:

- run `php artisan mcp:start app` manually
- confirm the CLI is running inside the Laravel project directory
- confirm the package is installed and discovered

If tools are visible but file writes are denied:

- verify the app environment is `local`
- verify `file_tools.allow_code_edits` is `true`
- verify the target path is inside an approved writable root
