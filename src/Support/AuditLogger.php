<?php

namespace LaravelMcpSuite\Support;

use Illuminate\Support\Facades\File;

class AuditLogger
{
    protected function path(): string
    {
        return storage_path('logs/laravel-mcp-audit.log');
    }

    public function clear(): void
    {
        File::ensureDirectoryExists(dirname($this->path()));
        File::put($this->path(), '');
    }

    public function record(string $tool, array $arguments, array $payload): void
    {
        File::ensureDirectoryExists(dirname($this->path()));

        $entry = [
            'timestamp' => now()->toIso8601String(),
            'tool' => $tool,
            'environment' => $payload['meta']['environment'] ?? config('app.env', app()->environment()),
            'result' => (($payload['data']['allowed'] ?? true) && ($payload['data']['success'] ?? true)) ? 'allowed' : 'denied',
            'arguments' => $arguments,
        ];

        File::append($this->path(), json_encode($entry, JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    public function all(): array
    {
        if (! File::exists($this->path())) {
            return [];
        }

        return collect(preg_split('/\R/', trim((string) File::get($this->path()))))
            ->filter()
            ->map(fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR))
            ->values()
            ->all();
    }
}
