<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Policies\EnvironmentPolicy;
use LaravelMcpSuite\Support\ArtisanCommandPolicy;

#[Description('Run allowlisted Artisan commands in safe environments only.')]
class LaravelArtisanRunSafeTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-artisan-run-safe';

    public function schema(JsonSchema $schema): array
    {
        return [
            'command' => $schema->string()->required()->description('Allowlisted Artisan command name.'),
            'arguments' => $schema->object()->description('Optional Artisan command arguments.'),
        ];
    }

    public function handle(Request $request, ArtisanCommandPolicy $commandPolicy, EnvironmentPolicy $environmentPolicy): ResponseFactory
    {
        $validated = $request->validate([
            'command' => ['required', 'string'],
            'arguments' => ['nullable', 'array'],
        ]);

        $environment = config('app.env', app()->environment());
        $command = $validated['command'];
        $arguments = $validated['arguments'] ?? [];

        if (! $environmentPolicy->writeToolsEnabled($environment)) {
            return $this->result($command, $arguments, false, false, 'Write-capable tools are disabled outside local.', $environment);
        }

        if (! $commandPolicy->allowed($command)) {
            return $this->result($command, $arguments, false, false, 'Command is not allowlisted for MCP execution.', $environment);
        }

        $exitCode = Artisan::call($command, $arguments);

        return $this->result(
            $command,
            $arguments,
            true,
            $exitCode === 0,
            trim(Artisan::output()),
            $environment,
        );
    }

    protected function result(string $command, array $arguments, bool $allowed, bool $success, string $output, string $environment): ResponseFactory
    {
        return $this->auditedResponse($this->name(), new Request([
            'command' => $command,
            'arguments' => $arguments,
        ]), [
            'summary' => $success ? 'Artisan command executed.' : 'Artisan command was not executed successfully.',
            'data' => [
                'command' => $command,
                'arguments' => $arguments,
                'allowed' => $allowed,
                'success' => $success,
                'output' => $output,
            ],
            'warnings' => $success ? [] : [$output],
            'meta' => [
                'module' => 'artisan',
                'read_only' => false,
                'environment' => $environment,
            ],
        ]);
    }
}
