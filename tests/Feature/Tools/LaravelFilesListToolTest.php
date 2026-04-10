<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelFilesListTool;
use LaravelMcpSuite\Support\FileEditor;
use LaravelMcpSuite\Tests\TestCase;

class LaravelFilesListToolTest extends TestCase
{
    public function test_it_lists_files_in_allowed_directories(): void
    {
        File::ensureDirectoryExists(base_path('app/Services'));
        File::put(base_path('app/Services/ExampleService.php'), "<?php\n\nclass ExampleService {}\n");

        $tool = $this->app->make(LaravelFilesListTool::class);
        $response = $tool->handle(new Request([
            'path' => 'app',
            'depth' => 2,
        ]), $this->app->make(FileEditor::class));
        $payload = $response->getStructuredContent();

        $this->assertSame('app', $payload['data']['path']);
        $this->assertContains('app/Services/ExampleService.php', $payload['data']['entries']);
        $this->assertTrue($payload['meta']['read_only']);
    }
}
