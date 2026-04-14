<?php

namespace LaravelMcpSuite\Tests\Unit;

use Illuminate\Support\Facades\DB;
use LaravelMcpSuite\Support\DatabaseMutator;
use LaravelMcpSuite\Tests\TestCase;

class DatabaseMutatorTest extends TestCase
{
    public function test_it_creates_one_row_and_returns_the_inserted_id(): void
    {
        $mutator = $this->app->make(DatabaseMutator::class);

        $result = $mutator->create('users', [
            'name' => 'Morgan',
            'email' => 'morgan@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue($result['created']);
        $this->assertIsInt($result['inserted_id']);
        $this->assertSame(1, DB::table('users')->where('email', 'morgan@example.com')->count());
    }

    public function test_it_updates_one_matching_row(): void
    {
        $mutator = $this->app->make(DatabaseMutator::class);
        $userId = DB::table('users')->value('id');

        $result = $mutator->update('users', 'id', $userId, [
            'name' => 'Updated Taylor',
        ]);

        $this->assertSame(1, $result['affected_rows']);
        $this->assertSame('Updated Taylor', DB::table('users')->where('id', $userId)->value('name'));
    }

    public function test_it_reports_zero_affected_rows_for_missing_updates(): void
    {
        $mutator = $this->app->make(DatabaseMutator::class);

        $result = $mutator->update('users', 'id', 999999, [
            'name' => 'Nobody',
        ]);

        $this->assertSame(0, $result['affected_rows']);
    }

    public function test_it_deletes_one_matching_row(): void
    {
        $mutator = $this->app->make(DatabaseMutator::class);
        $userId = DB::table('users')->insertGetId([
            'name' => 'Delete Me',
            'email' => 'deleteme@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $mutator->delete('users', 'id', $userId);

        $this->assertSame(1, $result['affected_rows']);
        $this->assertFalse(DB::table('users')->where('id', $userId)->exists());
    }

    public function test_it_reports_zero_affected_rows_for_missing_deletes(): void
    {
        $mutator = $this->app->make(DatabaseMutator::class);

        $result = $mutator->delete('users', 'id', 999999);

        $this->assertSame(0, $result['affected_rows']);
    }
}
