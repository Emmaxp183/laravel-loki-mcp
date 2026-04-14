<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelStorageWriteTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\StorageEditor;
use LaravelMcpSuite\Tests\TestCase;

class LaravelStorageWriteToolTest extends TestCase
{
    public function test_it_writes_allowed_storage_objects_when_enabled(): void
    {
        Storage::fake('local');

        $tool = $this->app->make(LaravelStorageWriteTool::class);
        $response = $tool->handle(new Request([
            'disk' => 'local',
            'path' => 'mcp/example.txt',
            'content' => 'hello world',
        ]), $this->app->make(StorageEditor::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertSame('local', $payload['data']['disk']);
        $this->assertSame('mcp/example.txt', $payload['data']['path']);
        $this->assertSame(11, $payload['data']['bytes']);
        $this->assertFalse($payload['data']['overwritten']);
        Storage::disk('local')->assertExists('mcp/example.txt');
        $this->assertSame('hello world', Storage::disk('local')->get('mcp/example.txt'));
    }

    public function test_it_rejects_existing_targets_when_overwrite_is_false(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('mcp/example.txt', 'before');

        $tool = $this->app->make(LaravelStorageWriteTool::class);
        $response = $tool->handle(new Request([
            'disk' => 'local',
            'path' => 'mcp/example.txt',
            'content' => 'after',
        ]), $this->app->make(StorageEditor::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        $this->assertSame('before', Storage::disk('local')->get('mcp/example.txt'));
    }

    public function test_it_denies_writes_when_storage_writes_are_disabled(): void
    {
        config()->set('laravel-mcp.storage_tools.allow_writes_in_local', false);

        Storage::fake('local');

        $tool = $this->app->make(LaravelStorageWriteTool::class);
        $response = $tool->handle(new Request([
            'disk' => 'local',
            'path' => 'mcp/example.txt',
            'content' => 'hello world',
        ]), $this->app->make(StorageEditor::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        Storage::disk('local')->assertMissing('mcp/example.txt');
    }
}
