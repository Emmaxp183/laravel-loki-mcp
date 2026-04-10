<?php

namespace LaravelMcpSuite\Tests\Feature;

use LaravelMcpSuite\Tests\TestCase;

class PackageBootTest extends TestCase
{
    public function test_service_provider_loads(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(\LaravelMcpSuite\LaravelMcpSuiteServiceProvider::class));
    }
}
