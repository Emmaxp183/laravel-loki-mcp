<?php

namespace LaravelMcpSuite\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use LaravelMcpSuite\Support\ClientSnippetRenderer;

class InstallMcpCommand extends Command
{
    protected $signature = 'mcp:install';

    protected $description = 'Install Laravel MCP Suite into the current Laravel app.';

    public function handle(ClientSnippetRenderer $renderer): int
    {
        $this->ensureFile(base_path('config/laravel-mcp.php'), dirname(__DIR__, 3).'/config/laravel-mcp.php');
        $this->ensureFile(base_path('config/mcp.php'), dirname(__DIR__, 3).'/vendor/laravel/mcp/config/mcp.php');
        $this->ensureFile(base_path('routes/ai.php'), dirname(__DIR__, 3).'/stubs/routes.ai.stub');
        $this->ensureFile(base_path('docs/project-conventions.md'), dirname(__DIR__, 3).'/stubs/project-conventions.stub');

        $this->line($renderer->codexCli());
        $this->newLine();
        $this->line($renderer->claudeCode());
        $this->newLine();
        $this->line($renderer->webMode());
        $this->newLine();
        $this->warn('write-capable tools are enabled automatically only in local');

        return self::SUCCESS;
    }

    protected function ensureFile(string $target, string $source): void
    {
        File::ensureDirectoryExists(dirname($target));

        if (! File::exists($target)) {
            File::copy($source, $target);
        }
    }
}
