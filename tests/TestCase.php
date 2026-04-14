<?php

namespace LaravelMcpSuite\Tests;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;
use LaravelMcpSuite\Support\AuditLogger;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        class_exists(Fixtures\Models\User::class);
        class_exists(Fixtures\Models\Project::class);

        if (! Schema::hasTable('users')) {
            Schema::create('users', function ($table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function ($table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->string('name');
                $table->timestamps();
            });
        }

        File::ensureDirectoryExists(storage_path('logs'));

        file_put_contents(storage_path('logs/laravel.log'), implode(PHP_EOL, [
            '[2026-04-06 10:00:00] local.ERROR: RuntimeException: Broken thing happened {"token":"Bearer secret-token","DB_PASSWORD":"hidden-value"}',
            '#0 /app/Http/Controllers/TestController.php(10): RuntimeException',
            '[2026-04-06 10:01:00] local.INFO: Background job started',
        ]));

        $this->app->make(AuditLogger::class)->clear();

        if (! \Illuminate\Support\Facades\DB::table('users')->exists()) {
            $userId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                'name' => 'Taylor',
                'email' => 'taylor@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Illuminate\Support\Facades\DB::table('projects')->insert([
                'user_id' => $userId,
                'name' => 'Beacon',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.env', 'local');
        $app['config']->set('app.debug', true);
        $app['config']->set('laravel-mcp.modules.queues', true);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            \LaravelMcpSuite\LaravelMcpSuiteServiceProvider::class,
        ];
    }

    protected function defineRoutes($router): void
    {
        if (! $router instanceof Router) {
            return;
        }

        $router->get('/health', fn () => 'ok')
            ->name('health')
            ->middleware('web');

        $router->get('/users/{user}', [Fixtures\TestRouteController::class, 'show'])
            ->name('users.show')
            ->middleware('auth:sanctum');

        $router->post('/users', [Fixtures\TestRouteController::class, 'store'])
            ->name('users.store')
            ->middleware('throttle:api');
    }
}
