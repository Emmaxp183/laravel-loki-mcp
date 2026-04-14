<?php

namespace LaravelMcpSuite\Tests\Unit;

use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\DatabaseMutationPolicy;
use PHPUnit\Framework\TestCase;

class DatabaseMutationPolicyTest extends TestCase
{
    public function test_it_allows_only_configured_tables(): void
    {
        $policy = new DatabaseMutationPolicy([
            'database_tools' => [
                'allowed_tables' => ['users', 'projects'],
                'allowed_keys' => ['id'],
                'max_rows_per_call' => 1,
            ],
        ]);

        $this->assertTrue($policy->allowsTable('users'));
        $this->assertFalse($policy->allowsTable('posts'));
    }

    public function test_it_allows_only_configured_key_columns(): void
    {
        $policy = new DatabaseMutationPolicy([
            'database_tools' => [
                'allowed_tables' => ['users'],
                'allowed_keys' => ['id', 'uuid'],
                'max_rows_per_call' => 1,
            ],
        ]);

        $this->assertTrue($policy->allowsKey('id'));
        $this->assertTrue($policy->allowsKey('uuid'));
        $this->assertFalse($policy->allowsKey('email'));
    }

    public function test_it_defaults_to_one_row_per_call(): void
    {
        $policy = new DatabaseMutationPolicy([
            'database_tools' => [
                'allowed_tables' => ['users'],
            ],
        ]);

        $this->assertSame(1, $policy->maxRowsPerCall());
    }

    public function test_environment_policy_has_dedicated_database_mutation_gates(): void
    {
        $policy = new EnvironmentPolicy([
            'database_tools' => [
                'allow_mutations_in_local' => true,
                'allow_mutations_elsewhere' => false,
            ],
        ]);

        $this->assertTrue($policy->databaseMutationsEnabled('local'));
        $this->assertFalse($policy->databaseMutationsEnabled('production'));
    }
}
