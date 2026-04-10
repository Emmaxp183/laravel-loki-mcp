<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Contracts\Foundation\Application;
use LaravelMcpSuite\Capabilities\Artisan\ArtisanCapabilities;
use LaravelMcpSuite\Capabilities\Core\CoreCapabilities;
use LaravelMcpSuite\Capabilities\Database\DatabaseCapabilities;
use LaravelMcpSuite\Capabilities\Files\FilesCapabilities;
use LaravelMcpSuite\Capabilities\Generators\GeneratorsCapabilities;
use LaravelMcpSuite\Capabilities\Logs\LogsCapabilities;
use LaravelMcpSuite\Capabilities\Queues\QueuesCapabilities;
use LaravelMcpSuite\Capabilities\Tests\TestsCapabilities;

class CapabilityRegistry
{
    public function __construct(
        protected Application $app,
    ) {
    }

    public function tools(): array
    {
        return $this->collect('tools');
    }

    public function resources(): array
    {
        return $this->collect('resources');
    }

    public function prompts(): array
    {
        return $this->collect('prompts');
    }

    protected function collect(string $method): array
    {
        $capabilities = [];

        foreach ($this->enabledModules() as $capabilityClass) {
            $capabilities = array_merge($capabilities, $this->app->make($capabilityClass)->{$method}());
        }

        return $capabilities;
    }

    protected function enabledModules(): array
    {
        $modules = $this->app['config']->get('laravel-mcp.modules', []);
        $map = [
            'core' => CoreCapabilities::class,
            'artisan' => ArtisanCapabilities::class,
            'database' => DatabaseCapabilities::class,
            'files' => FilesCapabilities::class,
            'logs' => LogsCapabilities::class,
            'tests' => TestsCapabilities::class,
            'queues' => QueuesCapabilities::class,
            'generators' => GeneratorsCapabilities::class,
        ];

        return collect($modules)
            ->filter()
            ->keys()
            ->map(fn (string $module): ?string => $map[$module] ?? null)
            ->filter()
            ->values()
            ->all();
    }
}
