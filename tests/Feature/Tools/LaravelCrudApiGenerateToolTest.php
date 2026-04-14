<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelCrudApiGenerateTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\CrudGenerator;
use LaravelMcpSuite\Tests\TestCase;

class LaravelCrudApiGenerateToolTest extends TestCase
{
    protected ?string $originalApiRoutes = null;

    protected bool $hadApiRoutes = false;

    protected function setUp(): void
    {
        parent::setUp();

        $path = base_path('routes/api.php');
        $this->hadApiRoutes = File::exists($path);
        $this->originalApiRoutes = $this->hadApiRoutes ? (string) File::get($path) : null;

        File::ensureDirectoryExists(dirname($path));
        File::put($path, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
    }

    protected function tearDown(): void
    {
        $this->restoreRouteFile('routes/api.php', $this->hadApiRoutes, $this->originalApiRoutes);
        $this->cleanupApiResource('Post');

        parent::tearDown();
    }

    public function test_it_denies_generation_when_code_edits_are_disabled(): void
    {
        config()->set('laravel-mcp.file_tools.allow_code_edits', false);

        $tool = $this->app->make(LaravelCrudApiGenerateTool::class);
        $response = $tool->handle(new Request([
            'resource' => 'Post',
            'fields' => [
                ['name' => 'title', 'type' => 'string', 'required' => true, 'rules' => ['string', 'max:255']],
            ],
        ]), $this->app->make(CrudGenerator::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        $this->assertSame('generators', $payload['meta']['module']);
    }

    public function test_it_generates_the_expected_api_file_set_and_route_once(): void
    {
        $tool = $this->app->make(LaravelCrudApiGenerateTool::class);
        $request = new Request([
            'resource' => 'Post',
            'fields' => [
                ['name' => 'title', 'type' => 'string', 'required' => true, 'rules' => ['string', 'max:255']],
                ['name' => 'body', 'type' => 'text', 'required' => true, 'rules' => ['string']],
            ],
        ]);

        $first = $tool->handle($request, $this->app->make(CrudGenerator::class), $this->app->make(EnvironmentPolicy::class))->getStructuredContent();
        $second = $tool->handle($request, $this->app->make(CrudGenerator::class), $this->app->make(EnvironmentPolicy::class))->getStructuredContent();

        $this->assertTrue($first['data']['allowed']);
        $this->assertSame('generators', $first['meta']['module']);
        $this->assertFileExists(base_path('app/Models/Post.php'));
        $this->assertFileExists(base_path('app/Http/Requests/StorePostRequest.php'));
        $this->assertFileExists(base_path('app/Http/Requests/UpdatePostRequest.php'));
        $this->assertFileExists(base_path('app/Http/Resources/PostResource.php'));
        $this->assertFileExists(base_path('app/Http/Controllers/Api/PostController.php'));
        $this->assertFileExists(base_path('tests/Feature/Api/PostCrudTest.php'));
        $this->assertNotEmpty(glob(base_path('database/migrations/*_create_posts_table.php')));
        $this->assertSame(
            1,
            substr_count((string) file_get_contents(base_path('routes/api.php')), "Route::apiResource('posts', \\App\\Http\\Controllers\\Api\\PostController::class);")
        );
        $this->assertContains('app/Models/Post.php', $first['data']['created']);
        $this->assertContains('routes/api.php', $first['data']['updated']);
    }

    protected function cleanupApiResource(string $resource): void
    {
        $paths = [
            "app/Models/{$resource}.php",
            "app/Http/Requests/Store{$resource}Request.php",
            "app/Http/Requests/Update{$resource}Request.php",
            "app/Http/Resources/{$resource}Resource.php",
            "app/Http/Controllers/Api/{$resource}Controller.php",
            "tests/Feature/Api/{$resource}CrudTest.php",
        ];

        foreach ($paths as $path) {
            File::delete(base_path($path));
        }

        foreach (glob(base_path('database/migrations/*_create_'.strtolower($resource).'s_table.php')) ?: [] as $migration) {
            File::delete($migration);
        }
    }

    protected function restoreRouteFile(string $relativePath, bool $hadOriginal, ?string $original): void
    {
        $path = base_path($relativePath);

        if ($hadOriginal) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, (string) $original);

            return;
        }

        File::delete($path);
    }
}
