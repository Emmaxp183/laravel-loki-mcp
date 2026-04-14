<?php

namespace LaravelMcpSuite\Support;

class StorageAccessPolicy
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config = [],
    ) {
    }

    public function allows(string $disk, string $path): bool
    {
        $normalizedDisk = $this->normalizeDisk($disk);
        $normalizedPath = $this->normalizePath($path);

        if ($normalizedDisk === '' || $normalizedPath === '') {
            return false;
        }

        if (! in_array($normalizedDisk, $this->allowedDisks(), true)) {
            return false;
        }

        foreach ($this->allowedPrefixes($normalizedDisk) as $prefix) {
            if ($normalizedPath === $prefix || str_starts_with($normalizedPath, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    public function normalizeDisk(string $disk): string
    {
        return trim($disk);
    }

    public function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '../') || str_starts_with($path, '..')) {
            return '';
        }

        return trim($path, '/');
    }

    public function withinByteLimit(int $bytes): bool
    {
        return $bytes <= $this->maxBytes();
    }

    public function maxBytes(): int
    {
        return (int) ($this->config['storage_tools']['max_bytes'] ?? 262144);
    }

    /**
     * @return array<int, string>
     */
    protected function allowedDisks(): array
    {
        $disks = $this->config['storage_tools']['allowed_disks'] ?? [];

        return array_values(array_filter(array_map(
            fn (mixed $disk): string => $this->normalizeDisk((string) $disk),
            is_array($disks) ? $disks : [],
        )));
    }

    /**
     * @return array<int, string>
     */
    protected function allowedPrefixes(string $disk): array
    {
        $prefixes = $this->config['storage_tools']['allowed_prefixes'][$disk] ?? [];

        return array_values(array_filter(array_map(
            fn (mixed $prefix): string => $this->normalizePath((string) $prefix),
            is_array($prefixes) ? $prefixes : [],
        )));
    }
}
