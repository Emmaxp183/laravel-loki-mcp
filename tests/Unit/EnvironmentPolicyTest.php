<?php

namespace LaravelMcpSuite\Tests\Unit;

use LaravelMcpSuite\Policies\EnvironmentPolicy;
use PHPUnit\Framework\TestCase;

class EnvironmentPolicyTest extends TestCase
{
    public function test_read_tools_are_enabled_in_all_supported_environments(): void
    {
        $policy = new EnvironmentPolicy([
            'write_tools' => [
                'enabled_in_local' => true,
                'enabled_elsewhere' => false,
            ],
        ]);

        $this->assertTrue($policy->readToolsEnabled('local'));
        $this->assertTrue($policy->readToolsEnabled('testing'));
        $this->assertTrue($policy->readToolsEnabled('staging'));
        $this->assertTrue($policy->readToolsEnabled('production'));
    }

    public function test_write_tools_are_enabled_in_local_only_by_default(): void
    {
        $policy = new EnvironmentPolicy([
            'write_tools' => [
                'enabled_in_local' => true,
                'enabled_elsewhere' => false,
            ],
        ]);

        $this->assertTrue($policy->writeToolsEnabled('local'));
        $this->assertFalse($policy->writeToolsEnabled('testing'));
        $this->assertFalse($policy->writeToolsEnabled('staging'));
        $this->assertFalse($policy->writeToolsEnabled('production'));
    }

    public function test_queue_mutations_have_dedicated_environment_gates(): void
    {
        $policy = new EnvironmentPolicy([
            'queue_tools' => [
                'allow_mutations_in_local' => true,
                'allow_mutations_elsewhere' => false,
            ],
        ]);

        $this->assertTrue($policy->queueMutationsEnabled('local'));
        $this->assertFalse($policy->queueMutationsEnabled('production'));
    }
}
