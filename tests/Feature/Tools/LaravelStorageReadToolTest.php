<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelStorageReadTool;
use LaravelMcpSuite\Support\StorageEditor;
use LaravelMcpSuite\Tests\TestCase;

class LaravelStorageReadToolTest extends TestCase
{
    public function test_it_reads_allowed_storage_objects(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('mcp/example.txt', 'hello world');

        $tool = $this->app->make(LaravelStorageReadTool::class);
        $response = $tool->handle(new Request([
            'disk' => 'local',
            'path' => 'mcp/example.txt',
        ]), $this->app->make(StorageEditor::class));
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertSame('local', $payload['data']['disk']);
        $this->assertSame('mcp/example.txt', $payload['data']['path']);
        $this->assertSame(11, $payload['data']['bytes']);
        $this->assertSame('hello world', $payload['data']['content']);
    }

    public function test_it_rejects_reads_that_exceed_the_byte_limit(): void
    {
        config()->set('laravel-mcp.storage_tools.max_bytes', 5);

        Storage::fake('local');
        Storage::disk('local')->put('mcp/example.txt', 'hello world');

        $tool = $this->app->make(LaravelStorageReadTool::class);
        $response = $tool->handle(new Request([
            'disk' => 'local',
            'path' => 'mcp/example.txt',
        ]), $this->app->make(StorageEditor::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        $this->assertNull($payload['data']['content']);
    }
}
