<?php

namespace LaravelMcpSuite\Tests\Unit;

use Illuminate\Support\Facades\File;
use LaravelMcpSuite\Support\CrudRouteWriter;
use LaravelMcpSuite\Support\FileEditor;
use LaravelMcpSuite\Tests\TestCase;

class CrudRouteWriterTest extends TestCase
{
    public function test_it_inserts_an_api_resource_route_once(): void
    {
        File::put(base_path('routes/api.php'), "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");

        $writer = $this->app->make(CrudRouteWriter::class);

        $first = $writer->write(
            $this->app->make(FileEditor::class),
            'api',
            'posts',
            '\\App\\Http\\Controllers\\Api\\PostController'
        );
        $second = $writer->write(
            $this->app->make(FileEditor::class),
            'api',
            'posts',
            '\\App\\Http\\Controllers\\Api\\PostController'
        );

        $content = (string) file_get_contents(base_path('routes/api.php'));

        $this->assertTrue($first['allowed']);
        $this->assertTrue($second['allowed']);
        $this->assertSame(1, substr_count($content, "Route::apiResource('posts', \\App\\Http\\Controllers\\Api\\PostController::class);"));
    }

    public function test_it_inserts_a_web_resource_route_once(): void
    {
        File::put(base_path('routes/web.php'), "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");

        $writer = $this->app->make(CrudRouteWriter::class);

        $writer->write(
            $this->app->make(FileEditor::class),
            'web',
            'posts',
            '\\App\\Http\\Controllers\\PostController'
        );
        $writer->write(
            $this->app->make(FileEditor::class),
            'web',
            'posts',
            '\\App\\Http\\Controllers\\PostController'
        );

        $content = (string) file_get_contents(base_path('routes/web.php'));

        $this->assertSame(1, substr_count($content, "Route::resource('posts', \\App\\Http\\Controllers\\PostController::class);"));
    }
}
