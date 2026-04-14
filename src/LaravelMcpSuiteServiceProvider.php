<?php

namespace LaravelMcpSuite;

use Illuminate\Support\ServiceProvider;
use LaravelMcpSuite\Console\Commands\InstallMcpCommand;
use LaravelMcpSuite\Capabilities\Artisan\ArtisanCapabilities;
use LaravelMcpSuite\Capabilities\Core\CoreCapabilities;
use LaravelMcpSuite\Capabilities\Database\DatabaseCapabilities;
use LaravelMcpSuite\Capabilities\Files\FilesCapabilities;
use LaravelMcpSuite\Capabilities\Generators\GeneratorsCapabilities;
use LaravelMcpSuite\Http\Middleware\EnsureSharedMcpToken;
use LaravelMcpSuite\Capabilities\Logs\LogsCapabilities;
use LaravelMcpSuite\Capabilities\Storage\StorageCapabilities;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\AiRouteRegistrar;
use LaravelMcpSuite\Support\CapabilityRegistry;
use LaravelMcpSuite\Support\CrudGenerator;
use LaravelMcpSuite\Support\CrudRouteWriter;
use LaravelMcpSuite\Support\DatabaseMutationPolicy;
use LaravelMcpSuite\Support\DatabaseMutator;
use LaravelMcpSuite\Support\FileDiffPreview;
use LaravelMcpSuite\Support\FileEditPolicy;
use LaravelMcpSuite\Support\FileEditor;
use LaravelMcpSuite\Capabilities\Queues\QueuesCapabilities;
use LaravelMcpSuite\Support\StorageAccessPolicy;
use LaravelMcpSuite\Support\StorageEditor;
use LaravelMcpSuite\Capabilities\Tests\TestsCapabilities;

class LaravelMcpSuiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-mcp.php', 'laravel-mcp');

        $this->app['config']->set('laravel-mcp', array_replace_recursive(
            require __DIR__.'/../config/laravel-mcp.php',
            $this->app['config']->get('laravel-mcp', []),
        ));

        $this->app->bind(EnvironmentPolicy::class, function ($app): EnvironmentPolicy {
            return new EnvironmentPolicy($app['config']->get('laravel-mcp', []));
        });
        $this->app->bind(DatabaseMutationPolicy::class, function ($app): DatabaseMutationPolicy {
            return new DatabaseMutationPolicy($app['config']->get('laravel-mcp', []));
        });
        $this->app->bind(DatabaseMutator::class, DatabaseMutator::class);

        $this->app->singleton(AiRouteRegistrar::class, AiRouteRegistrar::class);
        $this->app->singleton(CapabilityRegistry::class, CapabilityRegistry::class);
        $this->app->bind(CrudGenerator::class, CrudGenerator::class);
        $this->app->bind(CrudRouteWriter::class, CrudRouteWriter::class);
        $this->app->bind(FileEditPolicy::class, function ($app): FileEditPolicy {
            return new FileEditPolicy($app['config']->get('laravel-mcp', []));
        });
        $this->app->bind(StorageAccessPolicy::class, function ($app): StorageAccessPolicy {
            return new StorageAccessPolicy($app['config']->get('laravel-mcp', []));
        });
        $this->app->singleton(FileDiffPreview::class, FileDiffPreview::class);
        $this->app->bind(FileEditor::class, FileEditor::class);
        $this->app->bind(StorageEditor::class, StorageEditor::class);

        $this->app->singleton(CoreCapabilities::class, CoreCapabilities::class);
        $this->app->singleton(ArtisanCapabilities::class, ArtisanCapabilities::class);
        $this->app->singleton(DatabaseCapabilities::class, DatabaseCapabilities::class);
        $this->app->singleton(FilesCapabilities::class, FilesCapabilities::class);
        $this->app->singleton(LogsCapabilities::class, LogsCapabilities::class);
        $this->app->singleton(StorageCapabilities::class, StorageCapabilities::class);
        $this->app->singleton(TestsCapabilities::class, TestsCapabilities::class);
        $this->app->singleton(QueuesCapabilities::class, QueuesCapabilities::class);
        $this->app->singleton(GeneratorsCapabilities::class, GeneratorsCapabilities::class);
    }

    public function boot(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('laravel-mcp.shared-token', EnsureSharedMcpToken::class);

        $this->publishes([
            __DIR__.'/../config/laravel-mcp.php' => config_path('laravel-mcp.php'),
        ], 'laravel-mcp-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallMcpCommand::class,
            ]);
        }
    }
}
