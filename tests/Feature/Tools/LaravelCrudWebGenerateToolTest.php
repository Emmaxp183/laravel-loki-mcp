<?php

namespace LaravelMcpSuite\Tests\Feature\Tools;

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use LaravelMcpSuite\MCP\Tools\LaravelCrudWebGenerateTool;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\CrudGenerator;
use LaravelMcpSuite\Tests\TestCase;

class LaravelCrudWebGenerateToolTest extends TestCase
{
    protected ?string $originalWebRoutes = null;

    protected bool $hadWebRoutes = false;

    protected function setUp(): void
    {
        parent::setUp();

        $path = base_path('routes/web.php');
        $this->hadWebRoutes = File::exists($path);
        $this->originalWebRoutes = $this->hadWebRoutes ? (string) File::get($path) : null;

        File::ensureDirectoryExists(dirname($path));
        File::put($path, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
    }

    protected function tearDown(): void
    {
        $this->restoreRouteFile('routes/web.php', $this->hadWebRoutes, $this->originalWebRoutes);
        $this->cleanupWebResource('Page');

        parent::tearDown();
    }

    public function test_it_denies_generation_when_code_edits_are_disabled(): void
    {
        config()->set('laravel-mcp.file_tools.allow_code_edits', false);

        $tool = $this->app->make(LaravelCrudWebGenerateTool::class);
        $response = $tool->handle(new Request([
            'resource' => 'Page',
            'fields' => [
                ['name' => 'title', 'type' => 'string', 'required' => true, 'rules' => ['string', 'max:255']],
            ],
        ]), $this->app->make(CrudGenerator::class), $this->app->make(EnvironmentPolicy::class));
        $payload = $response->getStructuredContent();

        $this->assertFalse($payload['data']['allowed']);
        $this->assertSame('generators', $payload['meta']['module']);
    }

    public function test_it_generates_the_expected_web_file_set_and_route_once(): void
    {
        $tool = $this->app->make(LaravelCrudWebGenerateTool::class);
        $request = new Request([
            'resource' => 'Page',
            'fields' => [
                ['name' => 'title', 'type' => 'string', 'required' => true, 'rules' => ['string', 'max:255']],
                ['name' => 'body', 'type' => 'text', 'required' => true, 'rules' => ['string']],
            ],
        ]);

        $first = $tool->handle($request, $this->app->make(CrudGenerator::class), $this->app->make(EnvironmentPolicy::class))->getStructuredContent();
        $second = $tool->handle($request, $this->app->make(CrudGenerator::class), $this->app->make(EnvironmentPolicy::class))->getStructuredContent();

        $this->assertTrue($first['data']['allowed']);
        $this->assertFileExists(base_path('app/Models/Page.php'));
        $this->assertFileExists(base_path('app/Http/Requests/StorePageRequest.php'));
        $this->assertFileExists(base_path('app/Http/Requests/UpdatePageRequest.php'));
        $this->assertFileExists(base_path('app/Http/Controllers/PageController.php'));
        $this->assertFileExists(base_path('resources/views/pages/index.blade.php'));
        $this->assertFileExists(base_path('resources/views/pages/create.blade.php'));
        $this->assertFileExists(base_path('resources/views/pages/edit.blade.php'));
        $this->assertFileExists(base_path('resources/views/pages/show.blade.php'));
        $this->assertFileExists(base_path('resources/views/pages/_form.blade.php'));
        $this->assertFileExists(base_path('tests/Feature/Web/PageCrudTest.php'));
        $this->assertNotEmpty(glob(base_path('database/migrations/*_create_pages_table.php')));
        $this->assertSame(
            1,
            substr_count((string) file_get_contents(base_path('routes/web.php')), "Route::resource('pages', \\App\\Http\\Controllers\\PageController::class);")
        );
        $this->assertContains('resources/views/pages/index.blade.php', $first['data']['created']);
        $this->assertContains('routes/web.php', $first['data']['updated']);
    }

    protected function cleanupWebResource(string $resource): void
    {
        $paths = [
            "app/Models/{$resource}.php",
            "app/Http/Requests/Store{$resource}Request.php",
            "app/Http/Requests/Update{$resource}Request.php",
            "app/Http/Controllers/{$resource}Controller.php",
            "tests/Feature/Web/{$resource}CrudTest.php",
            'resources/views/'.strtolower($resource).'s/index.blade.php',
            'resources/views/'.strtolower($resource).'s/create.blade.php',
            'resources/views/'.strtolower($resource).'s/edit.blade.php',
            'resources/views/'.strtolower($resource).'s/show.blade.php',
            'resources/views/'.strtolower($resource).'s/_form.blade.php',
        ];

        foreach ($paths as $path) {
            File::delete(base_path($path));
        }

        File::deleteDirectory(base_path('resources/views/'.strtolower($resource).'s'));

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
