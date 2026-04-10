# Use With Codex CLI

This is the simplest way to connect Codex CLI to your Laravel app.

Start with local MCP first. Only move to HTTP if you really need a remote endpoint.

## Local MCP Setup

After you run:

```bash
php artisan mcp:install
```

add this server entry to your Codex CLI MCP configuration:

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

This assumes:

- the command runs from the Laravel project root
- the MCP handle is still `app`
- `routes/ai.php` is present

## What Codex CLI Can Access

With the default modules enabled, Codex CLI can discover tools such as:

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

Prompt helpers include:

- `debug-last-exception`
- `generate-feature-test`
- `review-route-controller-consistency`
- `scaffold-crud`

## Quick Check

To verify the Laravel side before involving Codex CLI:

```bash
php artisan mcp:start app
```

If that boots cleanly, the local MCP server is registered.

## Optional HTTP Mode

If your Codex CLI setup supports MCP over HTTP, the Laravel-side setup is:

1. Set `laravel-mcp.server.enable_web_server` to `true`
2. Set `LARAVEL_MCP_SHARED_TOKEN`
3. Keep `laravel-mcp.server.auth.mode` as `shared_token`
4. Expose the app at `/mcp/app`

Required request auth:

- `Authorization: Bearer <token>`
- or the configured shared-token header

This package only documents the Laravel-side setup. Use the Codex CLI HTTP MCP format that matches your installed version.

## Optional Passport OAuth

If you need OAuth metadata routes for a desktop flow:

1. Install `laravel/passport`
2. Set `laravel-mcp.server.auth.mode` to `passport_oauth`
3. Review `config/mcp.php`
4. Allow the redirect domains and custom schemes your client needs

The suite only registers OAuth metadata routes when Passport is installed.

## File Editing

In the current default config, patch and write are already enabled in `local`:

```php
'file_tools' => [
    'allow_code_edits' => true,
],
```

Outside `local`, or if you turn that flag off, writes are denied.

Even when writes are allowed, they are still restricted to approved Laravel source directories and blocked paths like `.env`.

## Troubleshooting

If Codex CLI cannot start the server:

- run `php artisan mcp:start app` manually
- confirm the CLI is running inside the Laravel project directory
- confirm the package is installed and discovered

If tools are visible but file writes are denied:

- verify the app environment is `local`
- verify `write_tools.enabled_in_local` is `true`
- verify `file_tools.allow_code_edits` is `true`
- verify the target path is inside an approved writable root
