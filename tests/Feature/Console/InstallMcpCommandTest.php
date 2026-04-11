<?php

namespace LaravelMcpSuite\Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use LaravelMcpSuite\Support\ClientSnippetRenderer;
use LaravelMcpSuite\Tests\TestCase;

class InstallMcpCommandTest extends TestCase
{
    public function test_it_scaffolds_config_routes_conventions_and_client_snippets(): void
    {
        File::delete(base_path('config/laravel-mcp.php'));
        File::delete(base_path('config/mcp.php'));
        File::delete(base_path('routes/ai.php'));
        File::delete(base_path('docs/project-conventions.md'));

        $this->artisan('mcp:install')
            ->expectsOutputToContain('Codex CLI')
            ->expectsOutputToContain('Claude Code')
            ->expectsOutputToContain('write-capable tools are enabled automatically only in local')
            ->assertExitCode(0);

        $this->assertStringContainsString(
            'codex mcp add laravel-app php artisan mcp:start app',
            $this->app->make(ClientSnippetRenderer::class)->codexCli()
        );
        $this->assertFileExists(base_path('config/laravel-mcp.php'));
        $this->assertFileExists(base_path('config/mcp.php'));
        $this->assertFileExists(base_path('routes/ai.php'));
        $this->assertFileExists(base_path('docs/project-conventions.md'));
        $this->assertStringContainsString('AiRouteRegistrar', (string) file_get_contents(base_path('routes/ai.php')));
    }
}
