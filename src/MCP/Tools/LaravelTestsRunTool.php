<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use Symfony\Component\Process\Process;

#[Description('Run a constrained PHPUnit or Pest target.')]
class LaravelTestsRunTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-tests-run';

    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()->description('Optional test path.'),
            'filter' => $schema->string()->description('Optional test filter.'),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'path' => ['nullable', 'string'],
            'filter' => ['nullable', 'string'],
        ]);

        $command = [PHP_BINARY, 'vendor/bin/phpunit'];

        if (! empty($validated['path'])) {
            $command[] = $validated['path'];
        }

        if (! empty($validated['filter'])) {
            $command[] = '--filter';
            $command[] = $validated['filter'];
        }

        $process = new Process($command, dirname(__DIR__, 3));
        $process->run();

        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Test command executed.',
            'data' => [
                'command' => implode(' ', $command),
                'success' => $process->isSuccessful(),
                'output' => trim($process->getOutput().$process->getErrorOutput()),
            ],
            'warnings' => [],
            'meta' => [
                'module' => 'tests',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
