<?php

namespace LaravelMcpSuite\Tests\Unit;

use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\StorageAccessPolicy;
use PHPUnit\Framework\TestCase;

class StorageAccessPolicyTest extends TestCase
{
    public function test_it_allows_only_configured_disks_and_prefixes(): void
    {
        $policy = new StorageAccessPolicy([
            'storage_tools' => [
                'allowed_disks' => ['local'],
                'allowed_prefixes' => [
                    'local' => ['mcp/', 'exports/reports/'],
                ],
                'max_bytes' => 1024,
            ],
        ]);

        $this->assertTrue($policy->allows('local', 'mcp/notes.txt'));
        $this->assertTrue($policy->allows('local', 'exports/reports/daily.txt'));
        $this->assertFalse($policy->allows('public', 'mcp/notes.txt'));
        $this->assertFalse($policy->allows('local', 'other/notes.txt'));
    }

    public function test_it_rejects_empty_and_traversal_paths_when_normalizing(): void
    {
        $policy = new StorageAccessPolicy([
            'storage_tools' => [
                'allowed_disks' => ['local'],
                'allowed_prefixes' => [
                    'local' => ['mcp/'],
                ],
                'max_bytes' => 1024,
            ],
        ]);

        $this->assertSame('', $policy->normalizePath('../secrets.txt'));
        $this->assertSame('', $policy->normalizePath(''));
        $this->assertSame('mcp/note.txt', $policy->normalizePath('/mcp/note.txt'));
    }

    public function test_it_enforces_the_configured_byte_limit(): void
    {
        $policy = new StorageAccessPolicy([
            'storage_tools' => [
                'allowed_disks' => ['local'],
                'allowed_prefixes' => [
                    'local' => ['mcp/'],
                ],
                'max_bytes' => 5,
            ],
        ]);

        $this->assertTrue($policy->withinByteLimit(5));
        $this->assertFalse($policy->withinByteLimit(6));
    }

    public function test_environment_policy_has_dedicated_storage_write_gates(): void
    {
        $policy = new EnvironmentPolicy([
            'storage_tools' => [
                'allow_writes_in_local' => true,
                'allow_writes_elsewhere' => false,
            ],
        ]);

        $this->assertTrue($policy->storageWritesEnabled('local'));
        $this->assertFalse($policy->storageWritesEnabled('production'));
    }
}
