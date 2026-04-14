<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelStorageDeleteTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\StorageEditor;
use LaravelMcpSuite\Tests\TestCase;

class LaravelStorageDeleteToolTest extends TestCase
{
    public function test_it_deletes_allowed_storage_objects_when_enabled(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('mcp/example.txt', 'hello world');

        $tool = $this->app->make(LaravelStorageDeleteTool::class);
        $response = $tool->handle(new Request([
            'disk' => 'local',
            'path' => 'mcp/example.txt',
        ]), $this->app->make(StorageEditor::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertTrue($payload['data']['deleted']);
        Storage::disk('local')->assertMissing('mcp/example.txt');
    }

    public function test_it_denies_deletes_when_storage_writes_are_disabled(): void
    {
        config()->set('laravel-mcp.storage_tools.allow_writes_in_local', false);

        Storage::fake('local');
        Storage::disk('local')->put('mcp/example.txt', 'hello world');

        $tool = $this->app->make(LaravelStorageDeleteTool::class);
        $response = $tool->handle(new Request([
            'disk' => 'local',
            'path' => 'mcp/example.txt',
        ]), $this->app->make(StorageEditor::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        Storage::disk('local')->assertExists('mcp/example.txt');
    }
}
