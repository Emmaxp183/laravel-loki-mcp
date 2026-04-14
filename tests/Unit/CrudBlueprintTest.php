<?php

namespace LaravelMcpSuite\Tests\Unit;

use LaravelMcpSuite\Support\CrudBlueprint;
use PHPUnit\Framework\TestCase;

class CrudBlueprintTest extends TestCase
{
    public function test_it_derives_shared_names_and_mode_specific_paths(): void
    {
        $api = new CrudBlueprint('Post', 'api');
        $web = new CrudBlueprint('Post', 'web');

        $this->assertSame('Post', $api->model());
        $this->assertSame('posts', $api->table());
        $this->assertSame('posts', $api->route());
        $this->assertSame('app/Http/Controllers/Api/PostController.php', $api->controllerPath());
        $this->assertSame('routes/api.php', $api->routeFilePath());
        $this->assertSame('tests/Feature/Api/PostCrudTest.php', $api->testPath());

        $this->assertSame('app/Http/Controllers/PostController.php', $web->controllerPath());
        $this->assertSame('routes/web.php', $web->routeFilePath());
        $this->assertSame('resources/views/posts/index.blade.php', $web->viewPath('index'));
        $this->assertSame('tests/Feature/Web/PostCrudTest.php', $web->testPath());
    }
}
