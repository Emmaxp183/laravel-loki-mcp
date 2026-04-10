<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelFilesPatchTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\FileEditor;
use LaravelMcpSuite\Tests\TestCase;

class LaravelFilesPatchToolTest extends TestCase
{
    public function test_it_applies_search_and_replace_in_local_when_code_edits_are_enabled(): void
    {
        config()->set('laravel-mcp.file_tools.allow_code_edits', true);
        File::put(base_path('app/Providers/TestProvider.php'), "<?php\n\nreturn 'before';\n");

        $tool = $this->app->make(LaravelFilesPatchTool::class);
        $response = $tool->handle(new Request([
            'path' => 'app/Providers/TestProvider.php',
            'search' => 'before',
            'replace' => 'after',
        ]), $this->app->make(FileEditor::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertStringContainsString('after', (string) File::get(base_path('app/Providers/TestProvider.php')));
        $this->assertStringContainsString('+++ app/Providers/TestProvider.php', $payload['data']['diff_preview']);
    }

    public function test_it_rejects_writes_when_code_edits_are_disabled(): void
    {
        config()->set('laravel-mcp.file_tools.allow_code_edits', false);
        File::put(base_path('app/Providers/TestProvider.php'), "<?php\n\nreturn 'before';\n");

        $tool = $this->app->make(LaravelFilesPatchTool::class);
        $response = $tool->handle(new Request([
            'path' => 'app/Providers/TestProvider.php',
            'search' => 'before',
            'replace' => 'after',
        ]), $this->app->make(FileEditor::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        $this->assertStringContainsString('before', (string) File::get(base_path('app/Providers/TestProvider.php')));
    }
}
