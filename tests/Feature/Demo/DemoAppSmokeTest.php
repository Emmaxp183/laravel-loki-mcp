<?php

namespace LaravelMcpSuite\Tests\Feature\Demo;

use PHPUnit\Framework\TestCase;

class DemoAppSmokeTest extends TestCase
{
    public function test_demo_app_is_a_real_laravel_application_wired_to_the_package(): void
    {
        $demoRoot = dirname(__DIR__, 3).'/demo/laravel-app';

        $this->assertFileExists($demoRoot.'/artisan');
        $this->assertFileExists($demoRoot.'/composer.json');
        $this->assertFileExists($demoRoot.'/routes/web.php');
        $this->assertFileExists($demoRoot.'/routes/ai.php');
        $this->assertFileExists($demoRoot.'/docs/project-conventions.md');

        $composer = json_decode((string) file_get_contents($demoRoot.'/composer.json'), true);

        $this->assertIsArray($composer['repositories'] ?? null);
        $this->assertSame('path', $composer['repositories'][0]['type'] ?? null);
        $this->assertSame('../../', $composer['repositories'][0]['url'] ?? null);
        $this->assertArrayHasKey('emmanuelmensah/laravel-mcp-suite', $composer['require'] ?? []);
    }
}
