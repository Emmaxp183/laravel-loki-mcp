<?php

namespace LaravelMcpSuite\Support;

class ClientSnippetRenderer
{
    public function codexCli(): string
    {
        return <<<'TEXT'
Codex CLI
codex mcp add laravel-app php artisan mcp:start app
TEXT;
    }

    public function claudeCode(): string
    {
        return <<<'TEXT'
Claude Code
{
  "mcpServers": {
    "laravel-app": {
      "command": "php",
      "args": ["artisan", "mcp:start", "app"]
    }
  }
}
TEXT;
    }

    public function webMode(): string
    {
        return <<<'TEXT'
Web Mode
- Set `laravel-mcp.server.enable_web_server` to `true`
- For a quick protected setup, set `LARAVEL_MCP_SHARED_TOKEN` and keep `laravel-mcp.server.auth.mode` as `shared_token`
- For OAuth clients, install Laravel Passport, set `laravel-mcp.server.auth.mode` to `passport_oauth`, and review `config/mcp.php`
- See `docs/clients/codex-cli.md` and `docs/clients/claude-code.md` for remote setup examples
TEXT;
    }
}
