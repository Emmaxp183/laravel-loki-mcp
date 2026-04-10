<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelFilesReadTool;
use LaravelMcpSuite\Support\FileEditor;
use LaravelMcpSuite\Tests\TestCase;

class LaravelFilesReadToolTest extends TestCase
{
    public function test_it_reads_allowed_source_files(): void
    {
        File::put(base_path('routes/api.php'), "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");

        $tool = $this->app->make(LaravelFilesReadTool::class);
        $response = $tool->handle(new Request([
            'path' => 'routes/api.php',
        ]), $this->app->make(FileEditor::class));
        $payload = $response->getStructuredContent();

        $this->assertSame('routes/api.php', $payload['data']['path']);
        $this->assertStringContainsString('Illuminate\\Support\\Facades\\Route', $payload['data']['content']);
    }

    public function test_it_rejects_blocked_paths(): void
    {
        File::put(base_path('.env'), "APP_KEY=base64:secret\n");

        $tool = $this->app->make(LaravelFilesReadTool::class);
        $response = $tool->handle(new Request([
            'path' => '.env',
        ]), $this->app->make(FileEditor::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        $this->assertSame('.env', $payload['data']['path']);
    }
}
