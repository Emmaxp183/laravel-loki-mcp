<?php

namespace LaravelMcpSuite\MCP\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LaravelMcpSuite\Concerns\AuditsToolCalls;
use LaravelMcpSuite\Support\ModelInspector;

#[Description('Describe a specific Eloquent model.')]
class LaravelModelDescribeTool extends Tool
{
    use AuditsToolCalls;

    protected string $name = 'laravel-model-describe';

    public function schema(JsonSchema $schema): array
    {
        return [
            'model' => $schema->string()->required()->description('Fully qualified model class name.'),
        ];
    }

    public function handle(Request $request, ModelInspector $inspector): ResponseFactory
    {
        $validated = $request->validate([
            'model' => ['required', 'string'],
        ]);

        return $this->auditedResponse($this->name(), $request, [
            'summary' => 'Laravel model description loaded.',
            'data' => [
                'model' => $inspector->describe($validated['model']),
            ],
            'warnings' => [],
            'meta' => [
                'module' => 'core',
                'read_only' => true,
                'environment' => app()->environment(),
            ],
        ]);
    }
}
