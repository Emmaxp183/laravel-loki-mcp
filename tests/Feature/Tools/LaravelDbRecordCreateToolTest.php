<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelDbRecordCreateTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\DatabaseMutationPolicy;
use LaravelMcpSuite\Support\DatabaseMutator;
use LaravelMcpSuite\Tests\TestCase;

class LaravelDbRecordCreateToolTest extends TestCase
{
    public function test_it_creates_one_record_for_an_allowlisted_table(): void
    {
        config()->set('laravel-mcp.database_tools.allowed_tables', ['users']);

        $tool = $this->app->make(LaravelDbRecordCreateTool::class);
        $response = $tool->handle(new Request([
            'table' => 'users',
            'record' => [
                'name' => 'Avery',
                'email' => 'avery@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]), $this->app->make(DatabaseMutator::class), $this->app->make(DatabaseMutationPolicy::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertTrue($payload['data']['allowed']);
        $this->assertTrue($payload['data']['created']);
        $this->assertSame('users', $payload['data']['table']);
        $this->assertSame(1, DB::table('users')->where('email', 'avery@example.com')->count());
    }

    public function test_it_denies_creates_when_mutations_are_disabled(): void
    {
        config()->set('laravel-mcp.database_tools.allow_mutations_in_local', false);
        config()->set('laravel-mcp.database_tools.allowed_tables', ['users']);

        $tool = $this->app->make(LaravelDbRecordCreateTool::class);
        $response = $tool->handle(new Request([
            'table' => 'users',
            'record' => [
                'name' => 'Avery',
                'email' => 'avery@example.com',
            ],
        ]), $this->app->make(DatabaseMutator::class), $this->app->make(DatabaseMutationPolicy::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        $this->assertSame(0, DB::table('users')->where('email', 'avery@example.com')->count());
    }

    public function test_it_denies_creates_for_disallowed_tables(): void
    {
        config()->set('laravel-mcp.database_tools.allowed_tables', ['users']);

        $tool = $this->app->make(LaravelDbRecordCreateTool::class);
        $response = $tool->handle(new Request([
            'table' => 'projects',
            'record' => [
                'name' => 'Skunkworks',
            ],
        ]), $this->app->make(DatabaseMutator::class), $this->app->make(DatabaseMutationPolicy::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
    }
}
