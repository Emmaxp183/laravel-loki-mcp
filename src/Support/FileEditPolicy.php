<?php

namespace LaravelMcpSuite\Support;

class FileEditPolicy
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config = [],
    ) {
    }

    public function allows(string $path): bool
    {
        $normalized = $this->normalize($path);

        if ($normalized === '') {
            return false;
        }

        foreach ($this->blockedPaths() as $prefix) {
            if ($normalized === $prefix || str_starts_with($normalized, $prefix.'/')) {
                return false;
            }
        }

        foreach ($this->writablePaths() as $prefix) {
            if ($normalized === $prefix || str_starts_with($normalized, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    public function normalize(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '../') || str_starts_with($path, '..')) {
            return '';
        }

        return trim($path, '/');
    }

    /**
     * @return array<int, string>
     */
    protected function writablePaths(): array
    {
        $paths = $this->config['file_tools']['writable_paths'] ?? ['app', 'routes', 'database', 'config', 'tests'];

        return array_values(array_filter(array_map(
            fn (mixed $path): string => $this->normalize((string) $path),
            is_array($paths) ? $paths : [],
        )));
    }

    /**
     * @return array<int, string>
     */
    protected function blockedPaths(): array
    {
        $paths = $this->config['file_tools']['blocked_paths'] ?? ['.env', 'vendor', 'storage', 'bootstrap/cache', 'node_modules'];

        return array_values(array_filter(array_map(
            fn (mixed $path): string => $this->normalize((string) $path),
            is_array($paths) ? $paths : [],
        )));
    }
}
