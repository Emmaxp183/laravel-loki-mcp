<?php

namespace LaravelMcpSuite\Tests\Unit;

use LaravelMcpSuite\Sanitizers\OutputSanitizer;
use PHPUnit\Framework\TestCase;

class OutputSanitizerTest extends TestCase
{
    public function test_it_redacts_common_secret_patterns(): void
    {
        $sanitizer = new OutputSanitizer();

        $result = $sanitizer->sanitize([
            'token' => 'Bearer abc123secret',
            'password' => 'super-secret',
            'api_key' => 'sk-live-abc123',
            'dsn' => 'mysql://user:pass@example.com:3306/db',
            'private_key' => '-----BEGIN PRIVATE KEY----- abc -----END PRIVATE KEY-----',
            'message' => 'DB_PASSWORD=hidden-value',
        ]);

        $this->assertSame('[REDACTED]', $result['password']);
        $this->assertSame('[REDACTED]', $result['api_key']);
        $this->assertStringNotContainsString('abc123secret', $result['token']);
        $this->assertStringNotContainsString('pass@example.com', $result['dsn']);
        $this->assertStringNotContainsString('BEGIN PRIVATE KEY', $result['private_key']);
        $this->assertStringNotContainsString('hidden-value', $result['message']);
    }
}
