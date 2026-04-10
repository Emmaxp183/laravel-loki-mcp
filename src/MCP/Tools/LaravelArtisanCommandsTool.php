<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Support\ArtisanCommandPolicy;

#[Description('List Artisan commands and whether they are MCP-allowlisted.')]
class LaravelArtisanCommandsTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-artisan-commands';

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Optional command name filter.'),
        ];
    }

    public function handle(Request $request, ArtisanCommandPolicy $policy): ResponseFactory
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string'],
        ]);

        $search = $validated['search'] ?? null;

        $commands = collect(Artisan::all())
            ->map(fn ($command, string $name): array => [
                'name' => $name,
                'description' => $command->getDescription(),
                'allowed' => $policy->allowed($name),
            ])
            ->filter(fn (array $command): bool => $search === null || str_contains($command['name'], $search))
            ->values()
            ->all();

        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Artisan command inventory loaded.',
            'data' => [
                'commands' => $commands,
            ],
            'warnings' => [],
            'meta' => [
                'module' => 'artisan',
                'read_only' => true,
                'environment' => config('app.env', app()->environment()),
            ],
        ]);
    }
}
