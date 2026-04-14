<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelDbRecordDeleteTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\DatabaseMutationPolicy;
use LaravelMcpSuite\Support\DatabaseMutator;
use LaravelMcpSuite\Tests\TestCase;

class LaravelDbRecordDeleteToolTest extends TestCase
{
    public function test_it_deletes_one_record_for_an_allowlisted_table_and_key(): void
    {
        config()->set('laravel-mcp.database_tools.allowed_tables', ['users']);
        config()->set('laravel-mcp.database_tools.allowed_keys', ['id']);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Delete Me',
            'email' => 'dbdelete@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tool = $this->app->make(LaravelDbRecordDeleteTool::class);
        $response = $tool->handle(new Request([
            'table' => 'users',
            'key' => 'id',
            'id' => $userId,
        ]), $this->app->make(DatabaseMutator::class), $this->app->make(DatabaseMutationPolicy::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertSame(1, $payload['data']['affected_rows']);
        $this->assertFalse(DB::table('users')->where('id', $userId)->exists());
    }

    public function test_it_denies_deletes_when_mutations_are_disabled(): void
    {
        config()->set('laravel-mcp.database_tools.allow_mutations_in_local', false);
        config()->set('laravel-mcp.database_tools.allowed_tables', ['users']);
        config()->set('laravel-mcp.database_tools.allowed_keys', ['id']);

        $userId = DB::table('users')->value('id');

        $tool = $this->app->make(LaravelDbRecordDeleteTool::class);
        $response = $tool->handle(new Request([
            'table' => 'users',
            'key' => 'id',
            'id' => $userId,
        ]), $this->app->make(DatabaseMutator::class), $this->app->make(DatabaseMutationPolicy::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        $this->assertTrue(DB::table('users')->where('id', $userId)->exists());
    }

    public function test_it_reports_zero_affected_rows_for_missing_records(): void
    {
        config()->set('laravel-mcp.database_tools.allowed_tables', ['users']);
        config()->set('laravel-mcp.database_tools.allowed_keys', ['id']);

        $tool = $this->app->make(LaravelDbRecordDeleteTool::class);
        $response = $tool->handle(new Request([
            'table' => 'users',
            'key' => 'id',
            'id' => 999999,
        ]), $this->app->make(DatabaseMutator::class), $this->app->make(DatabaseMutationPolicy::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertSame(0, $payload['data']['affected_rows']);
    }
}
