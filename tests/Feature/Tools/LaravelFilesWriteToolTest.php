<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelFilesWriteTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\FileEditor;
use LaravelMcpSuite\Tests\TestCase;

class LaravelFilesWriteToolTest extends TestCase
{
    public function test_it_writes_allowed_files_when_enabled(): void
    {
        config()->set('laravel-mcp.file_tools.allow_code_edits', true);

        $tool = $this->app->make(LaravelFilesWriteTool::class);
        $response = $tool->handle(new Request([
            'path' => 'tests/Feature/GeneratedExampleTest.php',
            'content' => "<?php\n\nit('works', fn () => expect(true)->toBeTrue());\n",
        ]), $this->app->make(FileEditor::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertFileExists(base_path('tests/Feature/GeneratedExampleTest.php'));
        $this->assertStringContainsString('GeneratedExampleTest.php', $payload['data']['path']);
    }
}
