<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelStorageListTool;
use LaravelMcpSuite\Support\StorageEditor;
use LaravelMcpSuite\Tests\TestCase;

class LaravelStorageListToolTest extends TestCase
{
    public function test_it_lists_allowed_storage_objects(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('mcp/notes.txt', 'hello');
        Storage::disk('local')->put('mcp/reports/daily.txt', 'report');
        Storage::disk('local')->put('private/secret.txt', 'secret');

        $tool = $this->app->make(LaravelStorageListTool::class);
        $response = $tool->handle(new Request([
            'disk' => 'local',
            'path' => 'mcp/',
        ]), $this->app->make(StorageEditor::class));
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertSame('local', $payload['data']['disk']);
        $this->assertSame('mcp', $payload['data']['path']);
        $this->assertSame(['mcp/notes.txt', 'mcp/reports/daily.txt'], $payload['data']['entries']);
    }

    public function test_it_rejects_disallowed_storage_prefixes(): void
    {
        Storage::fake('local');

        $tool = $this->app->make(LaravelStorageListTool::class);
        $response = $tool->handle(new Request([
            'disk' => 'local',
            'path' => 'private/',
        ]), $this->app->make(StorageEditor::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        $this->assertSame([], $payload['data']['entries']);
    }
}
