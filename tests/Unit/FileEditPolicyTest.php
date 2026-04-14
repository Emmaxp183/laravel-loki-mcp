<?php

namespace LaravelMcpSuite\Tests\Unit;

use LaravelMcpSuite\Support\FileEditPolicy;
use PHPUnit\Framework\TestCase;

class FileEditPolicyTest extends TestCase
{
    public function test_it_allows_expected_paths_and_blocks_sensitive_ones(): void
    {
        $policy = new FileEditPolicy([
            'file_tools' => [
                'writable_paths' => ['app', 'routes', 'database', 'config', 'tests', 'resources'],
                'blocked_paths' => ['.env', 'vendor', 'storage', 'bootstrap/cache', 'node_modules'],
            ],
        ]);

        $this->assertTrue($policy->allows('app/Models/User.php'));
        $this->assertTrue($policy->allows('tests/Feature/ExampleTest.php'));
        $this->assertTrue($policy->allows('resources/views/posts/index.blade.php'));
        $this->assertFalse($policy->allows('.env'));
        $this->assertFalse($policy->allows('vendor/laravel/framework/src/Application.php'));
        $this->assertFalse($policy->allows('storage/logs/laravel.log'));
    }
}
