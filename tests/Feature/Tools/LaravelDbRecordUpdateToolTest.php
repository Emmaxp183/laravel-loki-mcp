<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelDbRecordUpdateTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\DatabaseMutationPolicy;
use LaravelMcpSuite\Support\DatabaseMutator;
use LaravelMcpSuite\Tests\TestCase;

class LaravelDbRecordUpdateToolTest extends TestCase
{
    public function test_it_updates_one_record_for_an_allowlisted_table_and_key(): void
    {
        config()->set('laravel-mcp.database_tools.allowed_tables', ['users']);
        config()->set('laravel-mcp.database_tools.allowed_keys', ['id']);

        $userId = DB::table('users')->value('id');

        $tool = $this->app->make(LaravelDbRecordUpdateTool::class);
        $response = $tool->handle(new Request([
            'table' => 'users',
            'key' => 'id',
            'id' => $userId,
            'changes' => [
                'name' => 'Updated Taylor',
            ],
        ]), $this->app->make(DatabaseMutator::class), $this->app->make(DatabaseMutationPolicy::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertSame(1, $payload['data']['affected_rows']);
        $this->assertSame('Updated Taylor', DB::table('users')->where('id', $userId)->value('name'));
    }

    public function test_it_denies_updates_for_disallowed_keys(): void
    {
        config()->set('laravel-mcp.database_tools.allowed_tables', ['users']);
        config()->set('laravel-mcp.database_tools.allowed_keys', ['id']);

        $tool = $this->app->make(LaravelDbRecordUpdateTool::class);
        $response = $tool->handle(new Request([
            'table' => 'users',
            'key' => 'email',
            'id' => 'taylor@example.com',
            'changes' => [
                'name' => 'Updated Taylor',
            ],
        ]), $this->app->make(DatabaseMutator::class), $this->app->make(DatabaseMutationPolicy::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
    }

    public function test_it_reports_zero_affected_rows_for_missing_records(): void
    {
        config()->set('laravel-mcp.database_tools.allowed_tables', ['users']);
        config()->set('laravel-mcp.database_tools.allowed_keys', ['id']);

        $tool = $this->app->make(LaravelDbRecordUpdateTool::class);
        $response = $tool->handle(new Request([
            'table' => 'users',
            'key' => 'id',
            'id' => 999999,
            'changes' => [
                'name' => 'Updated Taylor',
            ],
        ]), $this->app->make(DatabaseMutator::class), $this->app->make(DatabaseMutationPolicy::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertSame(0, $payload['data']['affected_rows']);
    }
}
